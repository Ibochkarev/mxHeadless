<?php

declare(strict_types=1);

namespace MxHeadless\Audit;

interface AuditLogWriter
{
    /**
     * @param array{
     *     request_id: string,
     *     identity_key: string,
     *     api_key_id: int|null,
     *     method: string,
     *     path: string,
     *     context_key: string,
     *     status_code: int,
     *     duration_ms: int
     * } $entry
     */
    public function append(array $entry): bool;
}
