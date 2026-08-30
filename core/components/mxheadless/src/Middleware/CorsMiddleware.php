<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Http\Psr7Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = Psr7Factory::createResponse(204);

            if (!(bool) $this->modx->getOption('mxheadless_cors_enabled', null, false)) {
                return $response;
            }

            $origin = $request->getHeaderLine('Origin');
            $allowedOrigin = $this->resolveAllowedOrigin($origin, $this->parseOrigins());

            return $allowedOrigin !== null ? $this->applyCorsHeaders($response, $allowedOrigin) : $response;
        }

        if (!(bool) $this->modx->getOption('mxheadless_cors_enabled', null, false)) {
            return $handler->handle($request);
        }

        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = $this->resolveAllowedOrigin($origin, $this->parseOrigins());

        $response = $handler->handle($request);

        return $allowedOrigin !== null ? $this->applyCorsHeaders($response, $allowedOrigin) : $response;
    }

    /**
     * @return list<string>
     */
    private function parseOrigins(): array
    {
        $raw = (string) $this->modx->getOption('mxheadless_cors_allowed_origins', null, '');

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * @param list<string> $allowed
     */
    private function resolveAllowedOrigin(string $origin, array $allowed): ?string
    {
        if ($origin === '') {
            return null;
        }

        if ($allowed === ['*'] || in_array($origin, $allowed, true)) {
            return $origin;
        }

        return null;
    }

    private function applyCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
    {
        $methods = (string) $this->modx->getOption('mxheadless_cors_allowed_methods', null, 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        $headers = (string) $this->modx->getOption(
            'mxheadless_cors_allowed_headers',
            null,
            'Authorization,Content-Type,X-Request-ID,X-CSRF-Token,X-Context,X-API-Key,Idempotency-Key',
        );
        $expose = (string) $this->modx->getOption(
            'mxheadless_cors_expose_headers',
            null,
            'ETag,X-Request-ID,X-RateLimit-Limit,X-RateLimit-Remaining,X-RateLimit-Reset,Idempotency-Replayed',
        );
        $credentials = (bool) $this->modx->getOption('mxheadless_cors_allow_credentials', null, false);

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Access-Control-Allow-Headers', $headers)
            ->withHeader('Access-Control-Expose-Headers', $expose)
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Vary', 'Origin');

        if ($credentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
