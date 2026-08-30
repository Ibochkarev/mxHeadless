<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Security;

use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Definition\RelationDefinition;
use MxHeadless\Extension\ExtensionApi;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Services\SchemaService;
use MxHeadless\Tests\Support\TestDefinitions;
use PHPUnit\Framework\TestCase;

/**
 * Ensures only explicitly registered objects are exposed via the registry and extension API.
 */
final class ArbitraryClassTest extends TestCase
{
    public function testUnregisteredObjectIsNotAvailable(): void
    {
        $registry = new ObjectRegistry();
        $registry->register(TestDefinitions::article());

        self::assertNull($registry->get('modUser'));
        self::assertNull($registry->get('modUserGroup'));
        self::assertFalse($registry->has('modUser'));
    }

    public function testSchemaServiceExposesOnlyRegisteredObjects(): void
    {
        $registry = new ObjectRegistry();
        $registry->register(TestDefinitions::article());
        $registry->freeze();

        $schema = (new SchemaService($registry))->handle();

        self::assertArrayHasKey('articles', $schema['data']['objects']);
        self::assertArrayNotHasKey('modUser', $schema['data']['objects']);
        self::assertArrayNotHasKey('secret_internal', $schema['data']['objects']);
    }

    public function testCannotRegisterRelationForUnknownObject(): void
    {
        $registry = new ObjectRegistry();
        $routes = new RouteCollection();
        $api = new ExtensionApi($registry, $routes);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown object: products');

        $api->registerRelation('products', RelationDefinition::create('category')->to('categories'));
    }

    public function testRegisteredObjectUsesDeclaredClassNotClientInput(): void
    {
        $registry = new ObjectRegistry();
        $definition = ObjectDefinition::create('articles')
            ->setName('articles')
            ->class('Article')
            ->fields(['id'])
            ->readable();

        $registry->register($definition);

        self::assertSame('articles', $registry->get('articles')?->name());
        self::assertNull($registry->get('modUser'));
        self::assertFalse($registry->has('Article'));
    }

    public function testFreezePreventsLateRegistrationOfSensitiveObjects(): void
    {
        $registry = new ObjectRegistry();
        $registry->register(TestDefinitions::article());
        $registry->freeze();

        $sensitive = ObjectDefinition::create('modUser')
            ->setName('modUser')
            ->class('MODX\\Revolution\\modUser')
            ->fields(['id', 'username', 'password'])
            ->readable();

        try {
            $registry->register($sensitive);
            self::fail('Expected registry to reject registration after freeze');
        } catch (\Throwable) {
            self::assertNull($registry->get('modUser'));
        }
    }
}
