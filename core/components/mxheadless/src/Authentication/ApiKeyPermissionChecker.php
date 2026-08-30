<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

final class ApiKeyPermissionChecker implements PermissionChecker
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly array $scopes,
    ) {
    }

    public function allows(string $permission): bool
    {
        return in_array($permission, $this->scopes, true) || in_array('*', $this->scopes, true);
    }
}
