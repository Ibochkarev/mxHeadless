<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Authentication\Identity;
use MxHeadless\Exception\ForbiddenException;
use MxHeadless\Http\CsrfTokenStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        $isSession = $identity !== null && $identity->type() === Identity::TYPE_SESSION;

        if ($isSession) {
            $sessionToken = CsrfTokenStore::ensure();
            $request = $request->withAttribute('csrf_token', $sessionToken);
        }

        if (!(bool) $this->modx->getOption('mxheadless_csrf_enabled', null, true)) {
            return $this->withCsrfHeader($handler->handle($request), $isSession);
        }

        $method = strtoupper($request->getMethod());
        if (in_array($method, self::MUTATING_METHODS, true) && $isSession) {
            $token = $request->getHeaderLine(CsrfTokenStore::HEADER);
            $sessionToken = (string) $request->getAttribute('csrf_token');

            if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
                throw new ForbiddenException('Invalid CSRF token');
            }
        }

        return $this->withCsrfHeader($handler->handle($request), $isSession);
    }

    private function withCsrfHeader(ResponseInterface $response, bool $isSession): ResponseInterface
    {
        if (!$isSession) {
            return $response;
        }

        $token = CsrfTokenStore::peek();
        if ($token === '') {
            return $response;
        }

        return $response->withHeader(CsrfTokenStore::HEADER, $token);
    }
}
