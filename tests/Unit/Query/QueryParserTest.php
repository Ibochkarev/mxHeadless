<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Query;

use MODX\Revolution\modX;
use MxHeadless\Exception\ValidationException;
use MxHeadless\Query\FilterOperator;
use MxHeadless\Query\QueryParser;
use PHPUnit\Framework\TestCase;

final class QueryParserTest extends TestCase
{
    private QueryParser $parser;

    protected function setUp(): void
    {
        $this->parser = new QueryParser(new modX([
            'mxheadless.max_limit' => 100,
            'mxheadless.max_offset' => 100000,
            'mxheadless.max_fields' => 50,
            'mxheadless.max_include_relations' => 10,
            'mxheadless.max_include_depth' => 2,
            'mxheadless.allowed_contexts' => 'web,mgr',
        ]));
    }

    public function testParsesPaginationWithDefaults(): void
    {
        $query = $this->parser->parse('articles', []);

        self::assertSame('articles', $query->objectName());
        self::assertSame(20, $query->pagination()->limit());
        self::assertSame(0, $query->pagination()->offset());
        self::assertSame('web', $query->context());
        self::assertFalse($query->preview());
    }

    public function testParsesFiltersSortsAndFields(): void
    {
        $query = $this->parser->parse('articles', [
            'limit' => '10',
            'offset' => '5',
            'fields' => 'id,title',
            'sort' => '-published,+title',
            'filter' => [
                'published' => ['eq' => '1'],
                'parent' => ['in' => '1,2,3'],
            ],
            'include' => 'author,comments',
            'q' => 'hello',
            'preview' => 'true',
            'context' => 'mgr',
        ]);

        self::assertSame(10, $query->pagination()->limit());
        self::assertSame(5, $query->pagination()->offset());
        self::assertSame(['id', 'title'], $query->fields()->fields());
        self::assertSame(['author', 'comments'], $query->includes()->paths());
        self::assertSame('hello', $query->search());
        self::assertTrue($query->preview());
        self::assertSame('mgr', $query->context());

        self::assertCount(2, $query->filters());
        self::assertSame('published', $query->filters()[0]->field());
        self::assertSame(FilterOperator::Eq, $query->filters()[0]->operator());
        self::assertSame('1', $query->filters()[0]->value());

        self::assertSame(['1', '2', '3'], $query->filters()[1]->value());

        self::assertCount(2, $query->sorts());
        self::assertSame('published', $query->sorts()[0]->field());
        self::assertFalse($query->sorts()[0]->isAscending());
        self::assertSame('title', $query->sorts()[1]->field());
        self::assertTrue($query->sorts()[1]->isAscending());
    }

    public function testParsesColonDirectionAliases(): void
    {
        $query = $this->parser->parse('articles', [
            'sort' => 'menuindex:asc,-id,publishedon:desc',
        ]);

        self::assertCount(3, $query->sorts());
        self::assertSame('menuindex', $query->sorts()[0]->field());
        self::assertTrue($query->sorts()[0]->isAscending());
        self::assertSame('id', $query->sorts()[1]->field());
        self::assertFalse($query->sorts()[1]->isAscending());
        self::assertSame('publishedon', $query->sorts()[2]->field());
        self::assertFalse($query->sorts()[2]->isAscending());
    }

    public function testFilterShorthandEqualsEq(): void
    {
        $query = $this->parser->parse('articles', [
            'filter' => ['published' => '1'],
        ]);

        self::assertCount(1, $query->filters());
        self::assertSame('published', $query->filters()[0]->field());
        self::assertSame(FilterOperator::Eq, $query->filters()[0]->operator());
        self::assertSame('1', $query->filters()[0]->value());
    }

    public function testPageAliasComputesOffset(): void
    {
        $query = $this->parser->parse('articles', [
            'limit' => '10',
            'page' => '3',
        ]);

        self::assertSame(10, $query->pagination()->limit());
        self::assertSame(20, $query->pagination()->offset());
    }

    public function testExplicitOffsetWinsOverPage(): void
    {
        $query = $this->parser->parse('articles', [
            'limit' => '10',
            'page' => '3',
            'offset' => '7',
        ]);

        self::assertSame(7, $query->pagination()->offset());
    }

    public function testLimitIsCappedAtMax(): void
    {
        $query = $this->parser->parse('articles', ['limit' => '999']);

        self::assertSame(100, $query->pagination()->limit());
    }

    public function testContextFromHeaderOverridesQueryParam(): void
    {
        $query = $this->parser->parse('articles', ['context' => 'web'], 'mgr');

        self::assertSame('mgr', $query->context());
    }

    public function testInvalidContextThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid context');

        $this->parser->parse('articles', ['context' => 'secret']);
    }

    public function testTooManyFieldsThrows(): void
    {
        $fields = implode(',', range(1, 51));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Too many fields requested');

        $this->parser->parse('articles', ['fields' => $fields]);
    }

    public function testIncludeDepthExceededThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Include depth exceeded');

        $this->parser->parse('articles', ['include' => 'a.b.c']);
    }

    public function testInvalidFilterOperatorThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid filter operator');

        $this->parser->parse('articles', [
            'filter' => ['id' => ['drop_table' => '1']],
        ]);
    }

    public function testParsesIncludeDeletedAliases(): void
    {
        $snake = $this->parser->parse('articles', ['include_deleted' => 'true']);
        self::assertTrue($snake->includeDeleted());

        $camel = $this->parser->parse('articles', ['includeDeleted' => '1']);
        self::assertTrue($camel->includeDeleted());

        $off = $this->parser->parse('articles', []);
        self::assertFalse($off->includeDeleted());
    }

    public function testInvalidLimitThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid limit');
        $this->parser->parse('articles', ['limit' => '0']);
    }

    public function testNegativeOffsetThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid offset');
        $this->parser->parse('articles', ['offset' => '-1']);
    }

    public function testInvalidPageThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid page');
        $this->parser->parse('articles', ['page' => '0']);
    }

    public function testNonNumericLimitThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid limit');
        $this->parser->parse('articles', ['limit' => 'abc']);
    }
}
