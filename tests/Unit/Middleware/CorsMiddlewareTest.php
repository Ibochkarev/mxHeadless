<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Middleware\CorsMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddlewareTest extends TestCase
{
    public function testOptionsWithCorsDisabledReturns204WithoutAcao(): void
    {
        $middleware = new CorsMiddleware(new modX(['mxheadless.cors.enabled' => false]));
        $request = new ServerRequest('OPTIONS', 'https://example.test/api/v1/resources');

        $response = $middleware->process($request, $this->okHandler());

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testOptionsWithAllowlistedOriginReturns204WithAcao(): void
    {
        $middleware = new CorsMiddleware(new modX([
            'mxheadless.cors.enabled' => true,
            'mxheadless.cors.allowed_origins' => 'https://app.example.test,https://other.example.test',
        ]));
        $request = (new ServerRequest('OPTIONS', 'https://example.test/api/v1/resources'))
            ->withHeader('Origin', 'https://app.example.test');

        $response = $middleware->process($request, $this->okHandler());

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('https://app.example.test', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testOptionsWithEvilOriginReturns204WithoutAcao(): void
    {
        $middleware = new CorsMiddleware(new modX([
            'mxheadless.cors.enabled' => true,
            'mxheadless.cors.allowed_origins' => 'https://app.example.test',
        ]));
        $request = (new ServerRequest('OPTIONS', 'https://example.test/api/v1/resources'))
            ->withHeader('Origin', 'https://evil.example.test');

        $response = $middleware->process($request, $this->okHandler());

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testGetWithAllowlistedOriginAddsAcaoToHandlerResponse(): void
    {
        $middleware = new CorsMiddleware(new modX([
            'mxheadless.cors.enabled' => true,
            'mxheadless.cors.allowed_origins' => 'https://app.example.test',
        ]));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('Origin', 'https://app.example.test');

        $response = $middleware->process($request, $this->okHandler());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('https://app.example.test', $response->getHeaderLine('Access-Control-Allow-Origin'));
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
