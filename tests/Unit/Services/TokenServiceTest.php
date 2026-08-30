<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Services;

use MODX\Revolution\modX;
use MxHeadless\Authentication\OAuthClientRepository;
use MxHeadless\Authentication\OAuthTokenRepository;
use MxHeadless\Exception\InvalidGrantException;
use MxHeadless\Services\TokenService;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    public function testClientCredentialsIssuesBearerToken(): void
    {
        $modx = new modX([
            'mxheadless_oauth_enabled' => true,
            'mxheadless_oauth_token_ttl' => 1800,
        ]);
        $clients = new OAuthClientRepository($modx);
        $clients->addInMemory('ci-client', [
            'id' => 1,
            'client_id' => 'ci-client',
            'client_secret_hash' => password_hash('top-secret', PASSWORD_DEFAULT),
            'scopes' => ['resources.read'],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);

        $service = new TokenService($modx, $clients, new OAuthTokenRepository($modx));
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/auth/token'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\Nyholm\Psr7\Stream::create(json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => 'ci-client',
                'client_secret' => 'top-secret',
            ], JSON_THROW_ON_ERROR)));

        $payload = $service->handle($request);

        self::assertStringStartsWith('mxt_', $payload['data']['access_token']);
        self::assertSame('Bearer', $payload['data']['token_type']);
        self::assertSame(1800, $payload['data']['expires_in']);
        self::assertSame('resources.read', $payload['data']['scope']);
    }

    public function testDisabledEndpointRejectsGrant(): void
    {
        $modx = new modX(['mxheadless_oauth_enabled' => false]);
        $service = new TokenService($modx, new OAuthClientRepository($modx), new OAuthTokenRepository($modx));

        $this->expectException(InvalidGrantException::class);
        $service->handle(new ServerRequest('POST', 'https://example.test/api/v1/auth/token'));
    }
}
