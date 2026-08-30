<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use Psr\Http\Message\ServerRequestInterface;

final class Identity
{
    public const TYPE_ANONYMOUS = 'anonymous';
    public const TYPE_SESSION = 'session';
    public const TYPE_API_KEY = 'api_key';
    public const TYPE_OAUTH_TOKEN = 'oauth_token';

    public function __construct(
        private readonly string $type,
        private readonly string $key,
        private readonly PermissionChecker $permissions,
        private readonly ?int $userId = null,
        private readonly ?string $contextKey = null,
        private readonly ?int $apiKeyId = null,
        private readonly ?int $rateLimitMax = null,
        private readonly ?int $rateLimitWindow = null,
    ) {
    }

    public static function anonymous(): self
    {
        return new self(self::TYPE_ANONYMOUS, 'anonymous', new AnonymousPermissionChecker());
    }

    /**
     * @param array<string, mixed> $record
     */
    public static function fromApiKeyRecord(modX $modx, array $record): self
    {
        $lookupId = (string) ($record['lookup_id'] ?? '');
        $userId = isset($record['created_by']) ? (int) $record['created_by'] : null;

        return new self(
            self::TYPE_API_KEY,
            'api_key:' . $lookupId,
            new ApiKeyPermissionChecker(CredentialParser::scopeList($record['scopes'] ?? '')),
            $userId > 0 ? $userId : null,
            self::modxContextKey($modx),
            CredentialParser::positiveInt($record['id'] ?? null),
            CredentialParser::positiveInt($record['rate_limit_max'] ?? null),
            CredentialParser::positiveInt($record['rate_limit_window'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed>|null $client
     */
    public static function fromOAuthTokenRecord(modX $modx, array $record, ?array $client): self
    {
        $tokenId = (string) ($record['token_id'] ?? '');
        $userId = isset($record['user_id']) ? (int) $record['user_id'] : null;
        $rateLimitMax = null;
        $rateLimitWindow = null;

        if ($client !== null) {
            $rateLimitMax = CredentialParser::positiveInt($client['rate_limit_max'] ?? null);
            $rateLimitWindow = CredentialParser::positiveInt($client['rate_limit_window'] ?? null);
        }

        return new self(
            self::TYPE_OAUTH_TOKEN,
            'oauth_token:' . $tokenId,
            new ApiKeyPermissionChecker(CredentialParser::scopeList($record['scopes'] ?? null)),
            $userId !== null && $userId > 0 ? $userId : null,
            self::modxContextKey($modx),
            null,
            $rateLimitMax,
            $rateLimitWindow,
        );
    }

    public function applyToRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $request = $request
            ->withAttribute('identity', $this)
            ->withAttribute('identity_key', $this->key());

        if ($this->apiKeyId !== null) {
            $request = $request->withAttribute('api_key_id', $this->apiKeyId);
        }

        if ($this->rateLimitMax !== null) {
            $request = $request->withAttribute('rate_limit_max', $this->rateLimitMax);
        }

        if ($this->rateLimitWindow !== null) {
            $request = $request->withAttribute('rate_limit_window', $this->rateLimitWindow);
        }

        return $request;
    }

    private static function modxContextKey(modX $modx): ?string
    {
        return $modx->context ? $modx->context->get('key') : null;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function contextKey(): ?string
    {
        return $this->contextKey;
    }

    public function isAnonymous(): bool
    {
        return $this->type === self::TYPE_ANONYMOUS;
    }

    public function allows(string $permission): bool
    {
        return $this->permissions->allows($permission);
    }

    public function apiKeyId(): ?int
    {
        return $this->apiKeyId;
    }

    public function rateLimitMax(): ?int
    {
        return $this->rateLimitMax;
    }

    public function rateLimitWindow(): ?int
    {
        return $this->rateLimitWindow;
    }
}
