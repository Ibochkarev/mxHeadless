<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Http;

use MODX\Revolution\modX;
use MxHeadless\Http\Psr7RequestFactory;
use PHPUnit\Framework\TestCase;

final class Psr7RequestFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        $_SERVER = [];
        parent::tearDown();
    }

    public function testPrefersPhpGetOverEntityEncodedQueryString(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'HTTPS' => 'on',
            'REQUEST_URI' => '/api/v1/resources?limit=3&sort=-id',
            'QUERY_STRING' => 'limit=3&amp;sort=-id&amp;filter[id][gt]=10',
            'HTTP_ACCEPT' => 'application/json',
        ];
        $_GET = [
            'limit' => '3',
            'sort' => '-id',
            'filter' => ['id' => ['gt' => '10']],
            'q' => 'api/v1/resources',
        ];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('3', $request->getQueryParams()['limit'] ?? null);
        self::assertSame('-id', $request->getQueryParams()['sort'] ?? null);
        self::assertSame(['id' => ['gt' => '10']], $request->getQueryParams()['filter'] ?? null);
        self::assertArrayNotHasKey('q', $request->getQueryParams());
        self::assertStringContainsString('sort=-id', (string) $request->getUri());
        self::assertArrayNotHasKey('amp;sort', $request->getQueryParams());
    }

    public function testKeepsExplicitSearchQueryParam(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/api/v1/resources?q=news&limit=1',
            'QUERY_STRING' => 'q=news&limit=1',
            'HTTP_ACCEPT' => 'application/json',
        ];
        $_GET = [
            'q' => 'news',
            'limit' => '1',
        ];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('news', $request->getQueryParams()['q'] ?? null);
    }

    public function testDecodesEntityEncodedQueryStringWhenGetEmpty(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/api/v1/resources',
            'QUERY_STRING' => 'limit=3&amp;sort=-id',
            'HTTP_ACCEPT' => 'application/json',
        ];
        $_GET = [];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('3', $request->getQueryParams()['limit'] ?? null);
        self::assertSame('-id', $request->getQueryParams()['sort'] ?? null);
    }
}
