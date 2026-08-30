<?php

declare(strict_types=1);

namespace MxHeadless\Audit;

use MODX\Revolution\modX;
use MxHeadless\Model\modMxHeadlessApiLog;

final class AuditLogRepository implements AuditLogWriter
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

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
    public function append(array $entry): bool
    {
        /** @var modMxHeadlessApiLog|null $model */
        $model = $this->modx->newObject(modMxHeadlessApiLog::class);
        if ($model === null) {
            return false;
        }

        $model->fromArray([
            'request_id' => $entry['request_id'],
            'identity_key' => $entry['identity_key'],
            'api_key_id' => $entry['api_key_id'],
            'method' => $entry['method'],
            'path' => $entry['path'],
            'context_key' => $entry['context_key'],
            'status_code' => $entry['status_code'],
            'duration_ms' => $entry['duration_ms'],
            'created_on' => time(),
        ]);

        return $model->save();
    }

    public function pruneOlderThan(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        $cutoff = time() - ($days * 86400);

        return (int) $this->modx->removeCollection(modMxHeadlessApiLog::class, [
            'created_on:<' => $cutoff,
        ]);
    }
}
