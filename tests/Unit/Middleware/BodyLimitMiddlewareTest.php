<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use MxHeadless\Middleware\BodyLimitMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BodyLimitMiddlewareTest extends TestCase
{
    public function testPathLongerThanMaxUriBytesThrows414(): void
    {
        $middleware = new BodyLimitMiddleware(new modX(['mxheadless.max_uri_bytes' => 10]));
        $longPath = '/api/v1/' . str_repeat('a', 20);
        $request = new ServerRequest('GET', 'https://example.test' . $longPath);

        try {
            $middleware->process($request, $this->okHandler());
            self::fail('Expected HttpException');
        } catch (HttpException $e) {
            self::assertSame(414, $e->getStatusCode());
            self::assertSame('URI too long', $e->getMessage());
        }
    }

    public function testContentLengthGreaterThanMaxBodyBytesThrows413(): void
    {
        $middleware = new BodyLimitMiddleware(new modX(['mxheadless.max_body_bytes' => 100]));
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withHeader('Content-Length', '200');

        try {
            $middleware->process($request, $this->okHandler());
            self::fail('Expected HttpException');
        } catch (HttpException $e) {
            self::assertSame(413, $e->getStatusCode());
            self::assertSame('Request body too large', $e->getMessage());
        }
    }

    public function testWithinLimitsPasses(): void
    {
        $middleware = new BodyLimitMiddleware(new modX([
            'mxheadless.max_uri_bytes' => 2048,
            'mxheadless.max_body_bytes' => 1048576,
        ]));
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withHeader('Content-Length', '50');

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], '{}');
            }
        };
    }
}
