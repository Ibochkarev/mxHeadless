<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MxHeadless\Authorization\Authorizer;
use MxHeadless\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Authorizer $authorizer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');
        if ($route !== null) {
            $this->authorizer->authorizeEndpoint($request, $route);
        }

        return $handler->handle($request);
    }
}
