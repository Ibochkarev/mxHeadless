<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Query;

use MODX\Revolution\modX;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Exception\ValidationException;
use MxHeadless\Query\Filter;
use MxHeadless\Query\FilterOperator;
use MxHeadless\Query\ObjectQuery;
use MxHeadless\Query\Pagination;
use MxHeadless\Query\Sort;
use MxHeadless\Query\VisibilityPolicy;
use MxHeadless\Query\XpdoQueryCompiler;
use MxHeadless\Tests\Support\TestDefinitions;
use PHPUnit\Framework\TestCase;
use xPDOQuery;

final class XpdoQueryCompilerTest extends TestCase
{
    private XpdoQueryCompiler $compiler;

    protected function setUp(): void
    {
        $modx = new modX();
        $this->compiler = new XpdoQueryCompiler($modx, new VisibilityPolicy(new Authorizer($modx)));
    }

    public function testCompilesSelectFiltersSortsAndPagination(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery(
            'articles',
            [
                new Filter('published', FilterOperator::Eq, 1),
                new Filter('title', FilterOperator::Like, '%news%'),
            ],
            [new Sort('title', 'DESC')],
            new Pagination(10, 20),
        );

        $xpdoQuery = $this->compiler->compile($definition, $query, ['id', 'title']);

        self::assertInstanceOf(xPDOQuery::class, $xpdoQuery);
        self::assertSame('Article', $xpdoQuery->class);
        self::assertContains('id', $xpdoQuery->selects);
        self::assertContains('title', $xpdoQuery->selects);
        self::assertSame(10, $xpdoQuery->limitValue);
        self::assertSame(20, $xpdoQuery->offsetValue);
        self::assertNotEmpty($xpdoQuery->wheres);
        self::assertSame([['title', 'DESC']], $xpdoQuery->sorts);
        self::assertContains(['published' => 1], $xpdoQuery->wheres);
        self::assertContains(['title:LIKE' => '%news%'], $xpdoQuery->wheres);
    }

    public function testQualifiesSelectAndSortWithAlias(): void
    {
        $modx = new class extends modX {
            public function newQuery(string $class, $criteria = null, bool $cacheFlag = true, string $alias = ''): xPDOQuery
            {
                return new xPDOQuery($class, $alias !== '' ? $alias : 'modContext');
            }
        };
        $compiler = new XpdoQueryCompiler($modx, new VisibilityPolicy(new Authorizer($modx)));
        $definition = TestDefinitions::article();
        $query = new ObjectQuery('articles', [], [new Sort('title', 'ASC')]);

        $xpdoQuery = $compiler->compile($definition, $query, ['id', 'title']);

        self::assertContains('modContext.id', $xpdoQuery->selects);
        self::assertContains('modContext.title', $xpdoQuery->selects);
        self::assertSame([['modContext.title', 'ASC']], $xpdoQuery->sorts);
    }

    public function testUsesBoundParametersForEqualityFilter(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery('articles', [new Filter('id', FilterOperator::Eq, 42)]);

        $xpdoQuery = $this->compiler->compile($definition, $query, ['id']);

        self::assertContains(['id' => 42], $xpdoQuery->wheres);
    }

    public function testDefaultSortByIdWhenNoSortProvided(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery('articles');

        $xpdoQuery = $this->compiler->compile($definition, $query, ['id']);

        self::assertSame([['id', 'ASC']], $xpdoQuery->sorts);
    }

    public function testRejectsNonFilterableField(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery('articles', [new Filter('body', FilterOperator::Eq, 'x')]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Filter not allowed');

        $this->compiler->compile($definition, $query, ['id']);
    }

    public function testRejectsNonSortableField(): void
    {
        $definition = TestDefinitions::article()->filterable(['body']);
        $query = new ObjectQuery('articles', [], [new Sort('body', 'ASC')]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Sort not allowed');

        $this->compiler->compile($definition, $query, ['id']);
    }

    public function testRejectsNonReadableSelectField(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery('articles');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Field not allowed');

        $this->compiler->compile($definition, $query, ['secret_column']);
    }

    public function testCompileCountOmitsSortAndLimit(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery(
            'articles',
            [new Filter('published', FilterOperator::Eq, 1)],
            [new Sort('title', 'DESC')],
            new Pagination(5, 10),
        );

        $xpdoQuery = $this->compiler->compileCount($definition, $query);

        self::assertSame([], $xpdoQuery->selects);
        self::assertSame([], $xpdoQuery->sorts);
        self::assertNull($xpdoQuery->limitValue);
        self::assertContains(['published' => 1], $xpdoQuery->wheres);
    }

    public function testNormalizesBooleanFilterStrings(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery(
            'articles',
            [new Filter('published', FilterOperator::Eq, 'true')],
        );

        $xpdoQuery = $this->compiler->compile($definition, $query, ['id']);

        self::assertContains(['published' => 1], $xpdoQuery->wheres);
    }

    public function testRejectsInvalidBooleanFilterString(): void
    {
        $definition = TestDefinitions::article();
        $query = new ObjectQuery(
            'articles',
            [new Filter('published', FilterOperator::Eq, 'maybe')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid filter value');

        $this->compiler->compile($definition, $query, ['id']);
    }
}
