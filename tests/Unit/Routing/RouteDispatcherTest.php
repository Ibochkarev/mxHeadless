<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Routing;

use MxHeadless\Exception\NotFoundException;
use Nyholm\Psr7\ServerRequest;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteDispatcher;
use PHPUnit\Framework\TestCase;

final class RouteDispatcherTest extends TestCase
{
    public function testMissingRouteThrowsNotFound(): void
    {
        $dispatcher = new RouteDispatcher();
        $request = (new ServerRequest('GET', '/api/v1/unknown'))
            ->withAttribute('route', null);

        $this->expectException(NotFoundException::class);
        $dispatcher->dispatch($request);
    }

    public function testCallableHandlerIsInvoked(): void
    {
        $dispatcher = new RouteDispatcher();
        $route = new Route('test', ['GET'], '/hello', static fn (): array => ['data' => ['ok' => true], 'meta' => []]);
        $request = (new ServerRequest('GET', '/api/v1/hello'))
            ->withAttribute('route', $route)
            ->withAttribute('route_params', []);

        $response = $dispatcher->dispatch($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"ok":true', (string) $response->getBody());
    }

    public function testCreateRouteReturns201(): void
    {
        $dispatcher = new RouteDispatcher();
        $route = new Route(
            'resources.create',
            ['POST'],
            '/resources',
            static fn (): array => ['data' => ['id' => 1], 'meta' => []],
        );
        $request = (new ServerRequest('POST', '/api/v1/resources'))
            ->withAttribute('route', $route)
            ->withAttribute('route_params', []);

        $response = $dispatcher->dispatch($request);
        self::assertSame(201, $response->getStatusCode());
    }
}
