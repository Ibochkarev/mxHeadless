<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Idempotency\IdempotencyFingerprint;
use MxHeadless\Idempotency\IdempotencyStore;
use MxHeadless\Middleware\IdempotencyMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IdempotencyMiddlewareTest extends TestCase
{
    public function testCachesOnlySuccessfulResponses(): void
    {
        $modx = new modX();
        $store = new IdempotencyStore($modx, new ModxCacheAdapter($modx));
        $middleware = new IdempotencyMiddleware($store);

        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources', [], '{"ok":true}'))
            ->withHeader('Idempotency-Key', 'retry-key');
        $fingerprint = IdempotencyFingerprint::fromRequest($request, 'retry-key');

        $errorHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(422, [], '{"error":"validation"}');
            }
        };

        $middleware->process($request, $errorHandler);
        self::assertNull($store->find($fingerprint));

        $successHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(201, ['Content-Type' => 'application/json'], '{"data":{"id":"1"}}');
            }
        };

        $response = $middleware->process($request, $successHandler);
        self::assertSame(201, $response->getStatusCode());
        self::assertNotNull($store->find($fingerprint));
    }

    public function testDifferentBodyWithSameKeyConflicts(): void
    {
        $modx = new modX();
        $store = new IdempotencyStore($modx, new ModxCacheAdapter($modx));
        $middleware = new IdempotencyMiddleware($store);

        $handler = new class implements RequestHandlerInterface {
            public int $calls = 0;

            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                ++$this->calls;

                return new Response(201, ['Content-Type' => 'application/json'], '{"data":{"id":"' . $this->calls . '"}}');
            }
        };

        $first = (new ServerRequest('POST', 'https://example.test/api/v1/resources', [], '{"pagetitle":"A"}'))
            ->withHeader('Idempotency-Key', 'body-key');
        $middleware->process($first, $handler);

        $second = (new ServerRequest('POST', 'https://example.test/api/v1/resources', [], '{"pagetitle":"B"}'))
            ->withHeader('Idempotency-Key', 'body-key');

        $this->expectException(\MxHeadless\Exception\ConflictException::class);
        $middleware->process($second, $handler);
    }
}
