<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\ServiceDisabledException;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * When mxheadless.enabled is false, only discovery (/) and health (/health) remain available.
 */
final class ServiceGateMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const ALLOWED_WHEN_DISABLED = ['discovery', 'health'];

    public function __construct(
        private readonly modX $modx,
        private readonly ApiPrefix $apiPrefix,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isEnabled()) {
            return $handler->handle($request);
        }

        /** @var Route|null $route */
        $route = $request->getAttribute('route');
        if ($route instanceof Route && in_array($route->name(), self::ALLOWED_WHEN_DISABLED, true)) {
            return $handler->handle($request);
        }

        // Fallback for unmatched path edge cases after routing.
        $path = parse_url((string) $request->getUri(), PHP_URL_PATH) ?: '/';
        $relative = $this->apiPrefix->stripVersionedPrefix($path);
        if ($relative === '/' || $relative === '/health') {
            return $handler->handle($request);
        }

        throw new ServiceDisabledException();
    }

    private function isEnabled(): bool
    {
        $value = $this->modx->getOption('mxheadless.enabled', null, true);
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'no', 'off'], true);
    }
}
