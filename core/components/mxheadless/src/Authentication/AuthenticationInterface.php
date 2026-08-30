<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationInterface
{
    public function authenticate(ServerRequestInterface $request): ?Identity;
}
