<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use MxHeadless\Model\modMxHeadlessOAuthClient;

final class OAuthClientRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $memoryByClientId = [];

    /** @var array<int, array<string, mixed>> */
    private array $memoryById = [];

    public function __construct(
        private readonly modX $modx,
    ) {
    }

    /**
     * @param array<string, mixed> $record
     */
    public function addInMemory(string $clientId, array $record): void
    {
        $this->memoryByClientId[$clientId] = $record;
        if (isset($record['id'])) {
            $this->memoryById[(int) $record['id']] = $record;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $clientId, string $clientSecret): ?array
    {
        $record = $this->findByClientId($clientId);
        if ($record === null || !$this->isActive($record)) {
            return null;
        }

        $hash = (string) ($record['client_secret_hash'] ?? '');
        if ($hash === '' || !password_verify($clientSecret, $hash)) {
            return null;
        }

        return $record;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByClientId(string $clientId): ?array
    {
        if (isset($this->memoryByClientId[$clientId])) {
            return $this->memoryByClientId[$clientId];
        }

        /** @var modMxHeadlessOAuthClient|null $model */
        $model = $this->modx->getObject(modMxHeadlessOAuthClient::class, ['client_id' => $clientId]);
        if ($model instanceof modMxHeadlessOAuthClient) {
            return $model->toArray();
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $clientId): ?array
    {
        if ($clientId <= 0) {
            return null;
        }

        if (isset($this->memoryById[$clientId])) {
            return $this->memoryById[$clientId];
        }

        /** @var modMxHeadlessOAuthClient|null $model */
        $model = $this->modx->getObject(modMxHeadlessOAuthClient::class, ['id' => $clientId]);
        if ($model instanceof modMxHeadlessOAuthClient) {
            return $model->toArray();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     */
    public function allowsGrantType(array $record, string $grantType): bool
    {
        return in_array($grantType, CredentialParser::grantTypes($record['grant_types'] ?? null), true);
    }

    /**
     * @param array<string, mixed> $record
     * @return list<string>
     */
    public function scopesFor(array $record): array
    {
        return CredentialParser::scopeList($record['scopes'] ?? null);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function isActive(array $record): bool
    {
        return empty($record['revoked']);
    }
}
