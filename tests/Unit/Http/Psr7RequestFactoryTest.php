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

    public function testFallbackApiPhpBareMapsToVersionRoot(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/assets/components/mxheadless/api.php',
            'REQUEST_URI' => '/assets/components/mxheadless/api.php',
            'QUERY_STRING' => '',
            'PATH_INFO' => '',
        ];
        $_GET = [];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('/api/v1', $request->getUri()->getPath());
    }

    public function testFallbackApiPhpPathInfoMapsOntoPrefix(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'SCRIPT_NAME' => '/assets/components/mxheadless/api.php',
            'REQUEST_URI' => '/assets/components/mxheadless/api.php/v1/health',
            'PATH_INFO' => '/v1/health',
            'QUERY_STRING' => '',
        ];
        $_GET = [];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('/api/v1/health', $request->getUri()->getPath());
    }

    public function testFallbackApiPhpRouteQueryMapsOntoPrefix(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'SCRIPT_NAME' => '/assets/components/mxheadless/api.php',
            'REQUEST_URI' => '/assets/components/mxheadless/api.php?route=/v1/health&limit=1',
            'QUERY_STRING' => 'route=/v1/health&limit=1',
            'PATH_INFO' => '',
        ];
        $_GET = [
            'route' => '/v1/health',
            'limit' => '1',
        ];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('/api/v1/health', $request->getUri()->getPath());
        self::assertSame('1', $request->getQueryParams()['limit'] ?? null);
        self::assertArrayNotHasKey('route', $request->getQueryParams());
    }

    public function testFallbackApiPhpAbsoluteRouteQuery(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'SCRIPT_NAME' => '/assets/components/mxheadless/api.php',
            'REQUEST_URI' => '/assets/components/mxheadless/api.php?route=/api/v1/resources',
            'QUERY_STRING' => 'route=/api/v1/resources',
            'PATH_INFO' => '',
        ];
        $_GET = [
            'route' => '/api/v1/resources',
        ];

        $request = (new Psr7RequestFactory(new modX()))->createFromGlobals();

        self::assertSame('/api/v1/resources', $request->getUri()->getPath());
    }
}
