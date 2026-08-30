<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Authentication;

use MODX\Revolution\modX;
use MxHeadless\Authentication\ApiKeyPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Authentication\OAuthClientRepository;
use MxHeadless\Authentication\OAuthTokenAuthenticator;
use MxHeadless\Authentication\OAuthTokenRepository;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class OAuthTokenAuthenticatorTest extends TestCase
{
    public function testAuthenticatesMxtBearerToken(): void
    {
        $modx = new modX();
        $tokens = new OAuthTokenRepository($modx);
        $clients = new OAuthClientRepository($modx);
        $plain = 'mxt_abc123_deadbeef';
        $tokens->addInMemory($plain, [
            'token_id' => 'abc123',
            'token_hash' => password_hash('deadbeef', PASSWORD_DEFAULT),
            'client_id' => 1,
            'scopes' => ['resources.read'],
            'expires_on' => time() + 3600,
            'revoked' => false,
        ]);
        $clients->addInMemory('test-client', [
            'id' => 1,
            'client_id' => 'test-client',
            'client_secret_hash' => password_hash('secret', PASSWORD_DEFAULT),
            'scopes' => ['resources.read'],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);

        $authenticator = new OAuthTokenAuthenticator($tokens, $clients, $modx);
        $identity = $authenticator->authenticate(
            (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
                ->withHeader('Authorization', 'Bearer ' . $plain),
        );

        self::assertNotNull($identity);
        self::assertSame(Identity::TYPE_OAUTH_TOKEN, $identity->type());
        self::assertTrue($identity->allows('resources.read'));
    }
}
