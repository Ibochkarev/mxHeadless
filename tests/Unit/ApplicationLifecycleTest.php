<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit;

use MxHeadless\ApplicationFactory;
use MODX\Revolution\modX;
use PHPUnit\Framework\TestCase;

final class ApplicationLifecycleTest extends TestCase
{
    public function testFactoryReturnsSingletonApplication(): void
    {
        $modx = new modX();
        $factory = new ApplicationFactory($modx);

        self::assertSame($factory->create(), $factory->create());
    }

    public function testBootstrapRegistersCoreObjectsAndRelations(): void
    {
        $modx = new modX();
        $app = (new ApplicationFactory($modx))->create();
        $app->bootstrap();

        $resources = $app->registry()->get('resources');
        self::assertNotNull($resources);
        self::assertNotNull($resources->getRelation('parent'));
        self::assertNotNull($resources->getRelation('children'));

        $contexts = $app->registry()->get('contexts');
        self::assertNotNull($contexts);
        self::assertTrue($contexts->isReadable());
        self::assertFalse($contexts->isCreatable());
        self::assertSame('key', $contexts->getPrimaryKey());
        self::assertTrue($contexts->isContextAccessGated());

        $chunks = $app->registry()->get('chunks');
        self::assertNotNull($chunks);
        self::assertTrue($chunks->isReadable());
        self::assertFalse($chunks->isCreatable());
    }
}
