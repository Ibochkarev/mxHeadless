<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use Psr\Http\Message\ServerRequestInterface;

final class OAuthTokenAuthenticator implements AuthenticationInterface
{
    public function __construct(
        private readonly OAuthTokenRepository $repository,
        private readonly OAuthClientRepository $clients,
        private readonly modX $modx,
    ) {
    }

    public function authenticate(ServerRequestInterface $request): ?Identity
    {
        $token = BearerTokenExtractor::extract($request, OAuthTokenRepository::PREFIX);
        if ($token === null) {
            return null;
        }

        $record = $this->repository->verify($token);
        if ($record === null) {
            return null;
        }

        $clientId = CredentialParser::positiveInt($record['client_id'] ?? null);
        $client = $clientId !== null ? $this->clients->findById($clientId) : null;

        return Identity::fromOAuthTokenRecord($this->modx, $record, $client);
    }
}
