<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Authentication;

use MxHeadless\Authentication\ApiKeyPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Authentication\SessionPermissionChecker;
use MODX\Revolution\modX;
use PHPUnit\Framework\TestCase;

final class IdentityAllowsTest extends TestCase
{
    public function testApiKeyAllowsWildcardScope(): void
    {
        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['*']),
        );

        self::assertTrue($identity->allows('resources.read'));
    }

    public function testSessionUsesModxPermissionChecker(): void
    {
        $modx = new class extends modX {
            public function hasPermission(string $permission): bool
            {
                return $permission === 'resources.read';
            }
        };

        $identity = new Identity(
            Identity::TYPE_SESSION,
            'user:1',
            new SessionPermissionChecker($modx),
            1,
        );

        self::assertTrue($identity->allows('resources.read'));
        self::assertFalse($identity->allows('resources.delete'));
    }
}
