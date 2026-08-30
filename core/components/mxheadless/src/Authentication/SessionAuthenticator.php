<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;
use Psr\Http\Message\ServerRequestInterface;

final class SessionAuthenticator implements AuthenticationInterface
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function authenticate(ServerRequestInterface $request): ?Identity
    {
        $user = $this->modx->getAuthenticatedUser();
        if ($user === null) {
            return null;
        }

        $userId = (int) $user->get('id');
        if ($userId <= 0) {
            return null;
        }

        return new Identity(
            Identity::TYPE_SESSION,
            'session:' . $userId,
            new SessionPermissionChecker($this->modx),
            $userId,
            $this->modx->context ? $this->modx->context->get('key') : null,
        );
    }
}
