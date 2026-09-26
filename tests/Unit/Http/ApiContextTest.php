<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Http;

use MODX\Revolution\modX;
use MxHeadless\Http\ApiContext;
use PHPUnit\Framework\TestCase;

final class ApiContextTest extends TestCase
{
    public function testDefaultIsWeb(): void
    {
        self::assertSame('web', ApiContext::bootstrapKey(new modX()));
    }

    public function testMgrFallsBackToWeb(): void
    {
        self::assertSame('web', ApiContext::bootstrapKey(new modX(['mxheadless_context' => 'mgr'])));
    }

    public function testCustomContextIsKept(): void
    {
        self::assertSame('site', ApiContext::bootstrapKey(new modX(['mxheadless_context' => 'site'])));
    }

    public function testApplySwitchesContext(): void
    {
        $modx = new modX(['mxheadless_context' => 'site']);
        self::assertSame('web', $modx->context?->get('key'));

        self::assertSame('site', ApiContext::apply($modx));
        self::assertSame('site', $modx->context?->get('key'));
    }
}
