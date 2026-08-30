<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use MxHeadless\Model\modMxHeadlessApiKey;

final class ApiKeyRepository
{
    public const PREFIX = 'mxh_';

    /** @var array<string, array<string, mixed>> */
    private array $memory = [];

    public function __construct(
        private readonly modX $modx,
    ) {
    }

    /**
     * @param array<string, mixed> $record
     */
    public function addInMemory(string $token, array $record): void
    {
        $this->memory[$token] = $record;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $token): ?array
    {
        if (isset($this->memory[$token])) {
            $record = $this->memory[$token];

            return $this->isActive($record) ? $record : null;
        }

        $parts = CredentialParser::splitPrefixedToken($token, self::PREFIX);
        if ($parts === null) {
            return null;
        }

        [$lookupId, $secret] = $parts;
        $record = $this->findByLookupId($lookupId);
        if ($record === null || !$this->isActive($record)) {
            return null;
        }

        $hash = (string) ($record['secret_hash'] ?? '');
        if ($hash === '' || !password_verify($secret, $hash)) {
            return null;
        }

        $this->touchLastUsed($record);

        return $record;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function touchLastUsed(array $record): void
    {
        $id = (int) ($record['id'] ?? 0);
        if ($id <= 0) {
            return;
        }

        $now = time();
        $previousTs = $this->normalizeTimestamp($record['last_used_on'] ?? null);
        if ($previousTs !== null && ($now - $previousTs) < 60) {
            return;
        }

        /** @var modMxHeadlessApiKey|null $model */
        $model = $this->modx->getObject(modMxHeadlessApiKey::class, $id);
        if (!$model instanceof modMxHeadlessApiKey) {
            return;
        }

        $model->set('last_used_on', $now);
        $model->save();
    }

    private function normalizeTimestamp(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        if (is_numeric($value)) {
            $ts = (int) $value;

            return $ts > 0 ? $ts : null;
        }

        $ts = strtotime((string) $value);

        return $ts !== false && $ts > 0 ? $ts : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findByLookupId(string $lookupId): ?array
    {
        /** @var modMxHeadlessApiKey|null $model */
        $model = $this->modx->getObject(modMxHeadlessApiKey::class, ['lookup_id' => $lookupId]);
        if ($model instanceof modMxHeadlessApiKey) {
            return $model->toArray();
        }

        foreach ($this->memory as $record) {
            if (($record['lookup_id'] ?? '') === $lookupId) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function isActive(array $record): bool
    {
        if (!empty($record['revoked'])) {
            return false;
        }

        $expires = $this->normalizeTimestamp($record['expires_on'] ?? null);
        if ($expires !== null && $expires < time()) {
            return false;
        }

        return true;
    }
}
