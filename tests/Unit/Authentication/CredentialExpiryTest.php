<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Authentication;

use MODX\Revolution\modX;
use MxHeadless\Authentication\ApiKeyRepository;
use MxHeadless\Authentication\OAuthTokenRepository;
use PHPUnit\Framework\TestCase;

final class CredentialExpiryTest extends TestCase
{
    public function testApiKeyAcceptsDatetimeExpiresOnFromMysql(): void
    {
        $repo = new ApiKeyRepository(new modX());
        $secret = 'aabbccddeeff00112233445566778899';
        $token = ApiKeyRepository::PREFIX . 'lookupid12345678_' . $secret;

        $repo->addInMemory($token, [
            'id' => 1,
            'lookup_id' => 'lookupid12345678',
            'secret_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'scopes' => ['resources.read'],
            'revoked' => false,
            'expires_on' => date('Y-m-d H:i:s', time() + 3600),
        ]);

        self::assertNotNull($repo->verify($token));
    }

    public function testApiKeyRejectsPastDatetimeExpiresOn(): void
    {
        $repo = new ApiKeyRepository(new modX());
        $secret = 'aabbccddeeff00112233445566778899';
        $token = ApiKeyRepository::PREFIX . 'lookupid87654321_' . $secret;

        $repo->addInMemory($token, [
            'id' => 2,
            'lookup_id' => 'lookupid87654321',
            'secret_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'scopes' => ['resources.read'],
            'revoked' => false,
            'expires_on' => date('Y-m-d H:i:s', time() - 10),
        ]);

        self::assertNull($repo->verify($token));
    }

    public function testOAuthTokenAcceptsDatetimeExpiresOnFromMysql(): void
    {
        $repo = new OAuthTokenRepository(new modX());
        $secret = '00112233445566778899aabbccddeeff';
        $token = OAuthTokenRepository::PREFIX . 'tokenid12345678_' . $secret;

        $repo->addInMemory($token, [
            'id' => 1,
            'token_id' => 'tokenid12345678',
            'token_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'scopes' => ['contexts.read'],
            'revoked' => false,
            'expires_on' => date('Y-m-d H:i:s', time() + 3600),
        ]);

        self::assertNotNull($repo->verify($token));
    }
}
