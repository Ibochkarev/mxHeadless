<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Audit;

use MODX\Revolution\modX;
use MxHeadless\Audit\AuditLogRepository;
use PHPUnit\Framework\TestCase;

final class AuditLogRepositoryTest extends TestCase
{
    public function testPruneOlderThanUsesArrayCriteria(): void
    {
        $modx = new class extends modX {
            /** @var array<string, mixed>|null */
            public ?array $lastRemoveCriteria = null;

            public function removeCollection(string $class, mixed $criteria = null): int
            {
                $this->lastRemoveCriteria = is_array($criteria) ? $criteria : null;

                return 3;
            }
        };

        $repository = new AuditLogRepository($modx);
        $removed = $repository->pruneOlderThan(90);

        self::assertSame(3, $removed);
        self::assertIsArray($modx->lastRemoveCriteria);
        self::assertArrayHasKey('created_on:<', $modx->lastRemoveCriteria);
        self::assertLessThan(time(), $modx->lastRemoveCriteria['created_on:<']);
    }

    public function testPruneOlderThanRejectsNonPositiveDays(): void
    {
        $repository = new AuditLogRepository(new modX());
        self::assertSame(0, $repository->pruneOlderThan(0));
        self::assertSame(0, $repository->pruneOlderThan(-1));
    }
}
