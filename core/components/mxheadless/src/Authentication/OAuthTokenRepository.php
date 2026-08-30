<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use MxHeadless\Model\modMxHeadlessOAuthToken;

final class OAuthTokenRepository
{
    public const PREFIX = 'mxt_';

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
     * @param list<string> $scopes
     * @return array{token: string, expires_in: int}
     */
    public function issue(int $clientId, array $scopes, ?int $userId = null): array
    {
        $tokenId = bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(16));
        $plain = self::PREFIX . $tokenId . '_' . $secret;
        $ttl = max(60, (int) $this->modx->getOption('mxheadless.oauth.token_ttl', null, 3600));

        /** @var modMxHeadlessOAuthToken|null $model */
        $model = $this->modx->newObject(modMxHeadlessOAuthToken::class);
        if ($model === null) {
            throw new \RuntimeException('Failed to create OAuth token');
        }

        $model->fromArray([
            'token_id' => $tokenId,
            'token_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'client_id' => $clientId,
            'user_id' => $userId,
            'scopes' => $scopes,
            'expires_on' => time() + $ttl,
            'revoked' => false,
            'created_on' => time(),
        ]);
        $model->save();

        return [
            'token' => $plain,
            'expires_in' => $ttl,
        ];
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

        [$tokenId, $secret] = $parts;
        $record = $this->findByTokenId($tokenId);
        if ($record === null || !$this->isActive($record)) {
            return null;
        }

        $hash = (string) ($record['token_hash'] ?? '');
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

        /** @var modMxHeadlessOAuthToken|null $model */
        $model = $this->modx->getObject(modMxHeadlessOAuthToken::class, $id);
        if (!$model instanceof modMxHeadlessOAuthToken) {
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
    private function findByTokenId(string $tokenId): ?array
    {
        /** @var modMxHeadlessOAuthToken|null $model */
        $model = $this->modx->getObject(modMxHeadlessOAuthToken::class, ['token_id' => $tokenId]);
        if ($model instanceof modMxHeadlessOAuthToken) {
            return $model->toArray();
        }

        foreach ($this->memory as $plain => $record) {
            if (($record['token_id'] ?? '') === $tokenId) {
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
