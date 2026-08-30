<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Security;

use MODX\Revolution\modX;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Exception\ValidationException;
use MxHeadless\Query\Filter;
use MxHeadless\Query\FilterOperator;
use MxHeadless\Query\ObjectQuery;
use MxHeadless\Query\Sort;
use MxHeadless\Query\VisibilityPolicy;
use MxHeadless\Query\XpdoQueryCompiler;
use MxHeadless\Tests\Support\TestDefinitions;
use PHPUnit\Framework\TestCase;

/**
 * Ensures client-controlled filter/sort input cannot escape whitelists or inject SQL.
 */
final class InjectionTest extends TestCase
{
    private XpdoQueryCompiler $compiler;

    protected function setUp(): void
    {
        $modx = new modX();
        $this->compiler = new XpdoQueryCompiler($modx, new VisibilityPolicy(new Authorizer($modx)));
    }

    /**
     * @return iterable<string, array{0: Filter}>
     */
    public static function maliciousFiltersProvider(): iterable
    {
        yield 'semicolon drop' => [new Filter('id; DROP TABLE users; --', FilterOperator::Eq, 1)];
        yield 'union select' => [new Filter('id UNION SELECT password FROM users', FilterOperator::Eq, 1)];
        yield 'backtick escape' => [new Filter('id` = 1 OR 1=1 --', FilterOperator::Eq, 1)];
        yield 'subquery field' => [new Filter('(SELECT 1)', FilterOperator::Eq, 1)];
    }

    /**
     * @dataProvider maliciousFiltersProvider
     */
    public function testMaliciousFilterFieldIsRejected(Filter $filter): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery('articles', [$filter]);

        try {
            $this->compiler->compile($definition, $query, ['id']);
            self::fail('Expected ValidationException for malicious filter field');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('Filter not allowed', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{0: Sort}>
     */
    public static function maliciousSortsProvider(): iterable
    {
        yield 'stacked expression' => [new Sort('id); DELETE FROM modx_users; --', 'ASC')];
        yield 'second column' => [new Sort('id, (SELECT SLEEP(5))', 'ASC')];
        yield 'backtick break' => [new Sort('id` DESC; --', 'ASC')];
    }

    /**
     * @dataProvider maliciousSortsProvider
     */
    public function testMaliciousSortFieldIsRejected(Sort $sort): void
    {
        $definition = TestDefinitions::article()->sorts(['id', 'title', 'published']);
        $query = new ObjectQuery('articles', [], [$sort]);

        try {
            $this->compiler->compile($definition, $query, ['id']);
            self::fail('Expected ValidationException for malicious sort field');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('Sort not allowed', $exception->getMessage());
        }
    }

    public function testAllowedFilterBindsValueThroughWhereCriteria(): void
    {
        $definition = TestDefinitions::article();
        $maliciousValue = "1' OR '1'='1";
        $query = new ObjectQuery('articles', [new Filter('title', FilterOperator::Eq, $maliciousValue)]);

        $xpdoQuery = $this->compiler->compile($definition, $query, ['id']);

        self::assertContains(['title' => $maliciousValue], $xpdoQuery->wheres);
        foreach ($xpdoQuery->wheres as $where) {
            foreach (array_keys($where) as $key) {
                self::assertStringNotContainsString($maliciousValue, (string) $key);
            }
        }
    }
}
