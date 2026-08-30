<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Services;

use MxHeadless\Authentication\ApiKeyPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Exception\ForbiddenException;
use MxHeadless\Services\ContextSettingsService;
use MODX\Revolution\modX;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class ContextSettingsServiceTest extends TestCase
{
    public function testReturnsPublicContextOptionsForAllowedKey(): void
    {
        $modx = new modX(['mxheadless.allowed_contexts' => 'web,mgr']);
        $authorizer = new Authorizer($modx);
        $service = new ContextSettingsService($modx, $authorizer);
        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['context.web']),
        );
        $request = (new ServerRequest('GET', '/api/v1/contexts/web/settings'))->withAttribute('identity', $identity);

        $response = $service->get($request, 'web');

        self::assertSame('web', $response['data']['key']);
        self::assertSame('/web/', $response['data']['settings']['site_url']);
        self::assertSame('web.example.test', $response['data']['settings']['http_host']);
    }

    public function testRejectsContextWithoutAccess(): void
    {
        $modx = new modX(['mxheadless.allowed_contexts' => 'web,mgr']);
        $authorizer = new Authorizer($modx);
        $service = new ContextSettingsService($modx, $authorizer);
        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['context.web']),
        );
        $request = (new ServerRequest('GET', '/api/v1/contexts/mgr/settings'))->withAttribute('identity', $identity);

        $this->expectException(ForbiddenException::class);
        $service->get($request, 'mgr');
    }
}
