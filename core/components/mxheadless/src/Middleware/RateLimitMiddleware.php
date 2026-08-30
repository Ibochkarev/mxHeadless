<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use MxHeadless\RateLimit\RateLimiterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
        private readonly RateLimiterInterface $rateLimiter,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!(bool) $this->modx->getOption('mxheadless_rate_limit_enabled', null, true)) {
            return $handler->handle($request);
        }

        $identity = (string) ($request->getAttribute('identity_key') ?? 'anonymous');
        $ip = (string) ($request->getAttribute('client_ip') ?? 'unknown');
        $key = 'mxheadless:rate:' . sha1($identity . ':' . $ip);

        $limit = $request->getAttribute('rate_limit_max');
        $window = $request->getAttribute('rate_limit_window');
        $limit = $limit !== null ? (int) $limit : (int) $this->modx->getOption('mxheadless_rate_limit_max_requests', null, 120);
        $window = $window !== null ? (int) $window : (int) $this->modx->getOption('mxheadless_rate_limit_window_seconds', null, 60);

        $decision = $this->rateLimiter->attempt($key, $limit, $window);
        if (!$decision->allowed) {
            throw new HttpException(
                'Rate limit exceeded',
                429,
                'Too Many Requests',
                'https://mxheadless.dev/problems/rate-limited',
                [],
                'rate_limited',
                [
                    'X-RateLimit-Limit' => (string) $decision->limit,
                    'X-RateLimit-Remaining' => '0',
                    'X-RateLimit-Reset' => (string) $decision->resetAt,
                    'Retry-After' => (string) max(1, $decision->resetAt - time()),
                ],
            );
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $decision->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $decision->remaining)
            ->withHeader('X-RateLimit-Reset', (string) $decision->resetAt);
    }
}
