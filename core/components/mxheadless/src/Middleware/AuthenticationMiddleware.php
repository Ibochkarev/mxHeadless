<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MxHeadless\Authentication\AuthenticatorChain;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticatorChain $authenticators,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $this->authenticators->authenticate($request);

        return $handler->handle($identity->applyToRequest($request));
    }
}
