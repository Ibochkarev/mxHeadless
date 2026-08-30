<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use Psr\Http\Message\ServerRequestInterface;

final class ApiKeyAuthenticator implements AuthenticationInterface
{
    public function __construct(
        private readonly ApiKeyRepository $repository,
        private readonly modX $modx,
    ) {
    }

    public function authenticate(ServerRequestInterface $request): ?Identity
    {
        $token = BearerTokenExtractor::extract($request, ApiKeyRepository::PREFIX);
        if ($token === null) {
            return null;
        }

        $record = $this->repository->verify($token);
        if ($record === null) {
            return null;
        }

        return Identity::fromApiKeyRecord($this->modx, $record);
    }
}
