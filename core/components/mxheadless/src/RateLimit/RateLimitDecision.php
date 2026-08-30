<?php

declare(strict_types=1);

namespace MxHeadless\RateLimit;

final class RateLimitDecision
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $resetAt,
    ) {
    }
}
