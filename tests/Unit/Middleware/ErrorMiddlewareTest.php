<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use MxHeadless\Middleware\ErrorMiddleware;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class ErrorMiddlewareTest extends TestCase
{
    public function testHttpExceptionReturnsProblemResponseWithCorrectStatus(): void
    {
        $middleware = new ErrorMiddleware(new modX());
        $request = new ServerRequest('GET', 'https://example.test/api/v1/resources');

        $response = $middleware->process($request, $this->throwingHandler(
            new HttpException('Teapot', 418, 'I\'m a teapot'),
        ));

        self::assertSame(418, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame(418, $body['status']);
        self::assertSame('Teapot', $body['detail']);
    }

    public function testGenericThrowableWithoutDebugReturns500WithoutDebugKey(): void
    {
        $middleware = new ErrorMiddleware(new modX(['mxheadless_debug' => false]));
        $request = new ServerRequest('GET', 'https://example.test/api/v1/resources');

        $response = $middleware->process($request, $this->throwingHandler(
            new RuntimeException('Something broke'),
        ));

        self::assertSame(500, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame(500, $body['status']);
        self::assertArrayNotHasKey('debug', $body);
    }

    public function testGenericThrowableWithDebugReturns500WithDebugMessage(): void
    {
        $middleware = new ErrorMiddleware(new modX(['mxheadless_debug' => true]));
        $request = new ServerRequest('GET', 'https://example.test/api/v1/resources');

        $response = $middleware->process($request, $this->throwingHandler(
            new RuntimeException('Something broke'),
        ));

        self::assertSame(500, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame(500, $body['status']);
        self::assertIsArray($body['debug'] ?? null);
        self::assertSame('Something broke', $body['debug']['message']);
    }

    private function throwingHandler(\Throwable $throwable): RequestHandlerInterface
    {
        return new class($throwable) implements RequestHandlerInterface {
            public function __construct(private readonly \Throwable $throwable)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->throwable;
            }
        };
    }
}
