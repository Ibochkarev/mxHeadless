<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Registry;

use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Registry\RegistryFrozenException;
use MxHeadless\Tests\Support\TestDefinitions;
use PHPUnit\Framework\TestCase;

final class ObjectRegistryTest extends TestCase
{
    public function testRegisterAndGet(): void
    {
        $registry = new ObjectRegistry();
        $definition = TestDefinitions::article();

        $registry->register($definition);

        self::assertTrue($registry->has('articles'));
        self::assertSame($definition, $registry->get('articles'));
    }

    public function testGetReturnsNullForUnknownObject(): void
    {
        $registry = new ObjectRegistry();

        self::assertNull($registry->get('unknown'));
        self::assertFalse($registry->has('unknown'));
    }

    public function testAllReturnsRegisteredDefinitions(): void
    {
        $registry = new ObjectRegistry();
        $articles = TestDefinitions::article();
        $resources = TestDefinitions::resource();

        $registry->register($articles);
        $registry->register($resources);

        $all = $registry->all();

        self::assertCount(2, $all);
        self::assertArrayHasKey('articles', $all);
        self::assertArrayHasKey('resources', $all);
    }

    public function testRegisterOverwritesSameName(): void
    {
        $registry = new ObjectRegistry();
        $first = ObjectDefinition::create('items')->setName('items')->class('First')->fields(['id']);
        $second = ObjectDefinition::create('items')->setName('items')->class('Second')->fields(['id', 'title']);

        $registry->register($first);
        $registry->register($second);

        self::assertCount(1, $registry->all());
        self::assertSame($second, $registry->get('items'));
        self::assertSame(['id', 'title'], $registry->get('items')?->getFields());
    }

    public function testRegisterWithoutNameThrows(): void
    {
        $registry = new ObjectRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object definition requires a name');

        $registry->register(ObjectDefinition::create()->class('Orphan'));
    }

    public function testFreezePreventsFurtherRegistration(): void
    {
        $registry = new ObjectRegistry();
        $registry->register(TestDefinitions::article());
        $registry->freeze();

        self::assertTrue($registry->isFrozen());

        $this->expectException(RegistryFrozenException::class);

        $registry->register(TestDefinitions::resource());
    }
}
