<?php

declare(strict_types=1);

namespace MxHeadless\Idempotency;

use MODX\Revolution\modX;
use MxHeadless\Cache\ModxCacheAdapter;

final class IdempotencyStore
{
    public function __construct(
        private readonly modX $modx,
        private readonly ModxCacheAdapter $cache,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->modx->getOption('mxheadless_idempotency_enabled', null, true);
    }

    /**
     * @return array{status_code: int, body: string, headers: array<string, string>, payload_hash: string}|null
     */
    public function find(string $fingerprint): ?array
    {
        $record = $this->cache->get($this->recordKey($fingerprint), 'idempotency');
        if (!is_array($record) || !isset($record['status_code'], $record['body'])) {
            return null;
        }

        /** @var array<string, string> $headers */
        $headers = is_array($record['headers'] ?? null) ? $record['headers'] : [];

        return [
            'status_code' => (int) $record['status_code'],
            'body' => (string) $record['body'],
            'headers' => $headers,
            'payload_hash' => (string) ($record['payload_hash'] ?? ''),
        ];
    }

    public function acquire(string $fingerprint): bool
    {
        return $this->cache->add($this->lockKey($fingerprint), 1, 120, 'idempotency');
    }

    public function release(string $fingerprint): void
    {
        $this->cache->delete($this->lockKey($fingerprint), 'idempotency');
    }

    /**
     * @param array<string, string> $headers
     */
    public function save(
        string $fingerprint,
        string $idempotencyKey,
        int $statusCode,
        string $body,
        array $headers,
        string $payloadHash = '',
    ): void {
        $ttl = max(60, (int) $this->modx->getOption('mxheadless_idempotency_ttl', null, 86400));
        $this->cache->set($this->recordKey($fingerprint), [
            'idempotency_key' => $idempotencyKey,
            'status_code' => $statusCode,
            'body' => $body,
            'headers' => $headers,
            'payload_hash' => $payloadHash,
        ], $ttl, 'idempotency');
    }

    private function recordKey(string $fingerprint): string
    {
        return 'record/' . $fingerprint;
    }

    private function lockKey(string $fingerprint): string
    {
        return 'lock/' . $fingerprint;
    }
}
