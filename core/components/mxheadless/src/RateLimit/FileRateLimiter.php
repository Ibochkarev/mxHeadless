<?php

declare(strict_types=1);

namespace MxHeadless\RateLimit;

use MODX\Revolution\modX;

final class FileRateLimiter implements RateLimiterInterface
{
    private const CACHE_NAMESPACE = 'mxheadless/rate_limit';

    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function attempt(string $key, int $limit, int $windowSeconds): RateLimitDecision
    {
        if ($limit <= 0) {
            return new RateLimitDecision(true, 0, 0, time());
        }

        $cache = $this->modx->getCacheManager();
        $now = time();
        $bucket = $cache->get($key, [self::CACHE_NAMESPACE]);
        if (!is_array($bucket)) {
            $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];
        }

        if ($now >= (int) ($bucket['reset'] ?? 0)) {
            $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];
        }

        $resetAt = (int) $bucket['reset'];
        if ((int) $bucket['count'] >= $limit) {
            return new RateLimitDecision(false, $limit, 0, $resetAt);
        }

        $bucket['count'] = (int) $bucket['count'] + 1;
        $cache->set($key, $bucket, max(1, $resetAt - $now), [self::CACHE_NAMESPACE]);

        return new RateLimitDecision(
            true,
            $limit,
            max(0, $limit - (int) $bucket['count']),
            $resetAt,
        );
    }
}
