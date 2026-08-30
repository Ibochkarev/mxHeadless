<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\ServiceDisabledException;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Middleware\ServiceGateMiddleware;
use MxHeadless\Routing\Route;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ServiceGateMiddlewareTest extends TestCase
{
    public function testAllowsAllRoutesWhenEnabled(): void
    {
        $middleware = new ServiceGateMiddleware(new modX(['mxheadless.enabled' => true]), new ApiPrefix(new modX()));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('route', new Route('resources.list', ['GET'], '/resources', static fn (): array => []));

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testAllowsDiscoveryAndHealthWhenDisabled(): void
    {
        $middleware = new ServiceGateMiddleware(new modX(['mxheadless.enabled' => false]), new ApiPrefix(new modX()));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/'))
            ->withAttribute('route', new Route('discovery', ['GET'], '/', static fn (): array => []));

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testBlocksOtherRoutesWhenDisabled(): void
    {
        $middleware = new ServiceGateMiddleware(new modX(['mxheadless.enabled' => false]), new ApiPrefix(new modX()));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('route', new Route('resources.list', ['GET'], '/resources', static fn (): array => []));

        $this->expectException(ServiceDisabledException::class);
        $middleware->process($request, $this->okHandler());
    }

    public function testTreatsStringZeroAsDisabled(): void
    {
        $middleware = new ServiceGateMiddleware(new modX(['mxheadless.enabled' => '0']), new ApiPrefix(new modX()));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('route', new Route('resources.list', ['GET'], '/resources', static fn (): array => []));

        $this->expectException(ServiceDisabledException::class);
        $middleware->process($request, $this->okHandler());
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
