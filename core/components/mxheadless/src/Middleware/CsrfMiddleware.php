<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Authentication\Identity;
use MxHeadless\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!(bool) $this->modx->getOption('mxheadless_csrf_enabled', null, true)) {
            return $handler->handle($request);
        }

        $method = strtoupper($request->getMethod());
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $handler->handle($request);
        }

        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        if ($identity === null || $identity->type() !== Identity::TYPE_SESSION) {
            return $handler->handle($request);
        }

        $token = $request->getHeaderLine('X-CSRF-Token');
        $sessionToken = $_SESSION['mxheadless.csrf_token'] ?? '';

        if ($token === '' || $sessionToken === '' || !hash_equals((string) $sessionToken, $token)) {
            throw new ForbiddenException('Invalid CSRF token');
        }

        return $handler->handle($request);
    }
}
