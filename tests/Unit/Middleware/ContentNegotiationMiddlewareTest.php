<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MxHeadless\Exception\HttpException;
use MxHeadless\Middleware\ContentNegotiationMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class ContentNegotiationMiddlewareTest extends TestCase
{
    public function testAllowsJsonPost(): void
    {
        $middleware = new ContentNegotiationMiddleware();
        $request = (new ServerRequest('POST', '/api/v1/auth/token'))
            ->withHeader('Content-Type', 'application/json');

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testAllowsFormUrlencodedPostForOauthClients(): void
    {
        $middleware = new ContentNegotiationMiddleware();
        $request = (new ServerRequest('POST', '/api/v1/auth/token'))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testRejectsOtherMediaTypes(): void
    {
        $middleware = new ContentNegotiationMiddleware();
        $request = (new ServerRequest('POST', '/api/v1/resources'))
            ->withHeader('Content-Type', 'text/plain');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unsupported media type');
        $middleware->process($request, $this->okHandler());
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new Response(200);
            }
        };
    }
}
