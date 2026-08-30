<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Authorization;

use MxHeadless\Authentication\ApiKeyPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Authorization\Authorizer;
use MODX\Revolution\modX;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class AuthorizerContextTest extends TestCase
{
    public function testAccessibleContextKeysRespectsApiKeyScopes(): void
    {
        $modx = new modX(['mxheadless_allowed_contexts' => 'web,mgr,europe']);
        $authorizer = new Authorizer($modx);
        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['context.web', 'context.europe']),
        );
        $request = (new ServerRequest('GET', '/api/v1/contexts'))->withAttribute('identity', $identity);

        self::assertSame(['web', 'europe'], $authorizer->accessibleContextKeys($request));
    }

    public function testAssertContextKeyAccessRejectsMissingScope(): void
    {
        $modx = new modX(['mxheadless_allowed_contexts' => 'web,mgr']);
        $authorizer = new Authorizer($modx);
        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['context.web']),
        );
        $request = (new ServerRequest('GET', '/api/v1/contexts/mgr'))->withAttribute('identity', $identity);

        $this->expectException(\MxHeadless\Exception\ForbiddenException::class);
        $authorizer->assertContextKeyAccess($request, 'mgr');
    }
}
