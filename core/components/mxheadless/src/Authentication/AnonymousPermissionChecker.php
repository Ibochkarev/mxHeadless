<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

final class AnonymousPermissionChecker implements PermissionChecker
{
    public function allows(string $permission): bool
    {
        return false;
    }
}
