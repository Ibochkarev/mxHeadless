<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

interface PermissionChecker
{
    public function allows(string $permission): bool;
}
