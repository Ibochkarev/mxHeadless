<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MxHeadless\Exception\NotFoundException;
use MxHeadless\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Router $router,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $match = $this->router->match($request);
        if ($match === null) {
            throw new NotFoundException('Endpoint not found');
        }

        $request = $request
            ->withAttribute('route', $match['route'])
            ->withAttribute('route_params', $match['params'])
            ->withAttribute('cache_object', $match['route']->cacheTag($match['params']));

        return $handler->handle($request);
    }
}
