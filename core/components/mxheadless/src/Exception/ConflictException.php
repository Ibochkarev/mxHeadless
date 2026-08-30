<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class ConflictException extends HttpException
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(string $message = 'Conflict', array $extra = [], string $code = 'idempotency_conflict')
    {
        parent::__construct(
            $message,
            409,
            'Conflict',
            'https://mxheadless.dev/problems/conflict',
            $extra,
            $code,
        );
    }
}
