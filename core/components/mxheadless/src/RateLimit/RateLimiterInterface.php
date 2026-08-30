<?php

declare(strict_types=1);

namespace MxHeadless\RateLimit;

interface RateLimiterInterface
{
    public function attempt(string $key, int $limit, int $windowSeconds): RateLimitDecision;
}
