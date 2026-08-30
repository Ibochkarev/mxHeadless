<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Authentication\AnonymousPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Middleware\HttpCacheMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpCacheMiddlewareTest extends TestCase
{
    public function testAnonymousGetWithCacheEnabledSetsPublicCacheControlAndEtag(): void
    {
        $modx = new modX([
            'mxheadless_cache_enabled' => true,
            'mxheadless_cache_ttl' => 300,
        ]);
        $middleware = new HttpCacheMiddleware($modx, new ModxCacheAdapter($modx));
        $handler = new CountingHandler(new Response(200, [], '{"items":[]}'));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', Identity::anonymous());

        $response = $middleware->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('public', $response->getHeaderLine('Cache-Control'));
        self::assertNotSame('', $response->getHeaderLine('ETag'));
        self::assertSame(1, $handler->calls);
    }

    public function testSecondAnonymousGetHitsCacheWithoutCallingHandler(): void
    {
        $modx = new modX([
            'mxheadless_cache_enabled' => true,
            'mxheadless_cache_ttl' => 300,
        ]);
        $middleware = new HttpCacheMiddleware($modx, new ModxCacheAdapter($modx));
        $handler = new CountingHandler(new Response(200, [], '{"cached":true}'));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', Identity::anonymous());

        $middleware->process($request, $handler);
        $response = $middleware->process($request, $handler);

        self::assertSame(1, $handler->calls);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"cached":true}', (string) $response->getBody());
        self::assertStringContainsString('public', $response->getHeaderLine('Cache-Control'));
    }

    public function testAuthenticatedIdentityGetsPrivateNoStore(): void
    {
        $modx = new modX([
            'mxheadless_cache_enabled' => true,
            'mxheadless_cache_ttl' => 300,
        ]);
        $middleware = new HttpCacheMiddleware($modx, new ModxCacheAdapter($modx));
        $identity = new Identity(Identity::TYPE_API_KEY, 'api_key:abc', new AnonymousPermissionChecker());
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $identity);

        $response = $middleware->process($request, new CountingHandler(new Response(200, [], '{"private":true}')));

        self::assertSame('private, no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testIfNoneMatchMatchingReturns304(): void
    {
        $modx = new modX([
            'mxheadless_cache_enabled' => true,
            'mxheadless_cache_ttl' => 300,
        ]);
        $middleware = new HttpCacheMiddleware($modx, new ModxCacheAdapter($modx));
        $body = '{"items":[]}';
        $etag = hash('sha256', $body);
        $handler = new CountingHandler(new Response(200, [], $body));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', Identity::anonymous())
            ->withHeader('If-None-Match', '"' . $etag . '"');

        $response = $middleware->process($request, $handler);

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('"' . $etag . '"', $response->getHeaderLine('ETag'));
    }

    public function testHeadReturnsEmptyBodyWithEtag(): void
    {
        $modx = new modX([
            'mxheadless_cache_enabled' => true,
            'mxheadless_cache_ttl' => 300,
        ]);
        $middleware = new HttpCacheMiddleware($modx, new ModxCacheAdapter($modx));
        $body = '{"head":true}';
        $handler = new CountingHandler(new Response(200, [], $body));
        $request = (new ServerRequest('HEAD', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', Identity::anonymous());

        $response = $middleware->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertSame('"' . hash('sha256', $body) . '"', $response->getHeaderLine('ETag'));
    }
}

final class CountingHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public function __construct(
        private readonly ResponseInterface $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        ++$this->calls;

        return $this->response;
    }
}
