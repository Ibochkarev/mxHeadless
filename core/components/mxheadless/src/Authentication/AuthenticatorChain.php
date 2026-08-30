<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use Psr\Http\Message\ServerRequestInterface;

final class AuthenticatorChain implements AuthenticationInterface
{
    /**
     * @param list<AuthenticationInterface> $authenticators
     */
    public function __construct(
        private readonly array $authenticators,
    ) {
    }

    public function authenticate(ServerRequestInterface $request): Identity
    {
        foreach ($this->authenticators as $authenticator) {
            $identity = $authenticator->authenticate($request);
            if ($identity !== null) {
                return $identity;
            }
        }

        return Identity::anonymous();
    }
}
