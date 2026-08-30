<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MODX\Revolution\modX;

final class HealthService
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $dbOk = false;
        try {
            $dbOk = (bool) $this->modx->query('SELECT 1');
        } catch (\Throwable) {
            $dbOk = false;
        }

        return [
            'data' => [
                'status' => $dbOk ? 'ok' : 'degraded',
                'database' => $dbOk,
                'timestamp' => gmdate('c'),
            ],
            'meta' => [],
        ];
    }
}
