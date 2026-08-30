<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Webhook;

use MODX\Revolution\modX;
use MxHeadless\Webhook\WebhookDispatcher;
use MxHeadless\Webhook\WebhookOutbox;
use MxHeadless\Webhook\WebhookSigner;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class WebhookDispatcherUrlTest extends TestCase
{
    public function testBlocksPrivateHostsByDefault(): void
    {
        $dispatcher = new WebhookDispatcher(new modX(), new WebhookOutbox(new modX()), new WebhookSigner());
        $method = new ReflectionMethod(WebhookDispatcher::class, 'isSafeUrl');

        self::assertFalse($method->invoke($dispatcher, 'http://localhost/hook'));
        self::assertFalse($method->invoke($dispatcher, 'https://app.test/hook'));
        self::assertFalse($method->invoke($dispatcher, 'ftp://example.com/hook'));
    }

    public function testAllowsPrivateHostsWhenSettingEnabled(): void
    {
        $modx = new modX(['mxheadless.webhook.allow_private_urls' => true]);
        $dispatcher = new WebhookDispatcher($modx, new WebhookOutbox($modx), new WebhookSigner());
        $method = new ReflectionMethod(WebhookDispatcher::class, 'isSafeUrl');

        self::assertTrue($method->invoke($dispatcher, 'https://project.test/assets/hook.php'));
        self::assertTrue($method->invoke($dispatcher, 'http://localhost/hook'));
    }

    public function testAllowsPublicHttps(): void
    {
        $dispatcher = new WebhookDispatcher(new modX(), new WebhookOutbox(new modX()), new WebhookSigner());
        $method = new ReflectionMethod(WebhookDispatcher::class, 'isSafeUrl');

        self::assertTrue($method->invoke($dispatcher, 'https://example.com/revalidate'));
    }
}
