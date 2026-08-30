<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\NotFoundException;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Middleware\RoutingMiddleware;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Routing\Router;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoutingMiddlewareTest extends TestCase
{
    public function testMatchingRouteSetsRouteAttributeAndPasses(): void
    {
        $modx = new modX();
        $routes = new RouteCollection();
        $route = new Route('resources.list', ['GET'], '/resources', static fn (): array => []);
        $routes->add($route);
        $router = new Router($routes, new ApiPrefix($modx));
        $middleware = new RoutingMiddleware($router);

        $capturedRoute = null;
        $capturedParams = null;
        $request = new ServerRequest('GET', 'https://example.test/api/v1/resources');

        $response = $middleware->process($request, $this->capturingHandler($capturedRoute, $capturedParams));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($route, $capturedRoute);
        self::assertIsArray($capturedParams);
    }

    public function testUnknownPathThrowsNotFoundException(): void
    {
        $modx = new modX();
        $routes = new RouteCollection();
        $routes->add(new Route('resources.list', ['GET'], '/resources', static fn (): array => []));
        $router = new Router($routes, new ApiPrefix($modx));
        $middleware = new RoutingMiddleware($router);
        $request = new ServerRequest('GET', 'https://example.test/api/v1/unknown');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Endpoint not found');
        $middleware->process($request, $this->okHandler());
    }

    private function capturingHandler(mixed &$route, mixed &$params): RequestHandlerInterface
    {
        return new class($route, $params) implements RequestHandlerInterface {
            public function __construct(private mixed &$route, private mixed &$params)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->route = $request->getAttribute('route');
                $this->params = $request->getAttribute('route_params');

                return new Response(200, [], '{}');
            }
        };
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
