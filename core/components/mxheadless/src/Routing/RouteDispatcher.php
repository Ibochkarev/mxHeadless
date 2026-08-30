<?php

declare(strict_types=1);

namespace MxHeadless\Routing;

use MxHeadless\Exception\NotFoundException;
use MxHeadless\Http\Psr7Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteDispatcher implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');
        if (!$route instanceof Route) {
            throw new NotFoundException('Endpoint not found');
        }

        $params = $request->getAttribute('route_params') ?? [];
        if (!is_array($params)) {
            $params = [];
        }

        $result = ($route->handler())($request, $params);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $status = 200;
        if (
            strtoupper($request->getMethod()) === 'POST'
            && str_ends_with($route->name(), '.create')
        ) {
            $status = 201;
        }

        return Psr7Factory::json($result, $status);
    }
}
