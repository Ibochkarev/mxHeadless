<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use MODX\Revolution\modX;

final class SessionPermissionChecker implements PermissionChecker
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function allows(string $permission): bool
    {
        return $this->modx->hasPermission($permission);
    }
}
