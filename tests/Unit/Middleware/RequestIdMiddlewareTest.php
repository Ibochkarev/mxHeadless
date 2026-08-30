<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MxHeadless\Middleware\RequestIdMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestIdMiddlewareTest extends TestCase
{
    public function testValidXRequestIdIsEchoed(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('X-Request-ID', 'abc12345');

        $capturedId = null;
        $response = $middleware->process($request, $this->capturingHandler($capturedId));

        self::assertSame('abc12345', $capturedId);
        self::assertSame('abc12345', $response->getHeaderLine('X-Request-ID'));
    }

    public function testMissingRequestIdGenerates32HexId(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = new ServerRequest('GET', 'https://example.test/api/v1/resources');

        $capturedId = null;
        $response = $middleware->process($request, $this->capturingHandler($capturedId));

        self::assertIsString($capturedId);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $capturedId);
        self::assertSame($capturedId, $response->getHeaderLine('X-Request-ID'));
    }

    public function testInvalidRequestIdGenerates32HexId(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('X-Request-ID', 'bad!');

        $capturedId = null;
        $response = $middleware->process($request, $this->capturingHandler($capturedId));

        self::assertIsString($capturedId);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $capturedId);
        self::assertSame($capturedId, $response->getHeaderLine('X-Request-ID'));
    }

    private function capturingHandler(?string &$requestId): RequestHandlerInterface
    {
        return new class($requestId) implements RequestHandlerInterface {
            public function __construct(private ?string &$requestId)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->requestId = $request->getAttribute('request_id');

                return new Response(200, [], '{}');
            }
        };
    }
}
