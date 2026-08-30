<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Idempotency;

use MODX\Revolution\modX;
use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Idempotency\IdempotencyStore;
use PHPUnit\Framework\TestCase;

final class IdempotencyStoreTest extends TestCase
{
    public function testSaveAndFindRoundTrip(): void
    {
        $modx = new modX(['mxheadless.idempotency_ttl' => 3600]);
        $store = new IdempotencyStore($modx, new ModxCacheAdapter($modx));
        $fingerprint = hash('sha256', 'actor|/api/v1/resources|key-1');

        self::assertNull($store->find($fingerprint));

        $store->save($fingerprint, 'key-1', 201, '{"data":[]}', ['Content-Type' => 'application/json']);

        $cached = $store->find($fingerprint);
        self::assertNotNull($cached);
        self::assertSame(201, $cached['status_code']);
        self::assertSame('{"data":[]}', $cached['body']);
    }

    public function testAcquirePreventsParallelLocks(): void
    {
        $modx = new modX();
        $store = new IdempotencyStore($modx, new ModxCacheAdapter($modx));
        $fingerprint = hash('sha256', 'parallel');

        self::assertTrue($store->acquire($fingerprint));
        self::assertFalse($store->acquire($fingerprint));

        $store->release($fingerprint);
        self::assertTrue($store->acquire($fingerprint));
    }
}
