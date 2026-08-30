<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Services;

use MODX\Revolution\modX;
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Routing\RouteParameter;
use MxHeadless\Services\EndpointCatalogService;
use MxHeadless\Services\OpenApiGenerator;
use PHPUnit\Framework\TestCase;

final class MetaCatalogTest extends TestCase
{
    public function testEndpointCatalogListsRoutes(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route('discovery', ['GET'], '/', static fn (): array => [], null, true));
        $routes->add(new Route('resources.list', ['GET'], '/resources', static fn (): array => [], 'resources.read', true));

        $payload = (new EndpointCatalogService($routes))->handle();

        self::assertSame(2, $payload['meta']['count']);
        self::assertSame('discovery', $payload['data']['endpoints'][0]['name']);
        self::assertTrue($payload['data']['endpoints'][0]['public']);
    }

    public function testOpenApiGeneratorIncludesPathsAndSecurity(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route('meta.openapi', ['GET'], '/meta/openapi', static fn (): array => [], null, true));
        $routes->add(new Route('resources.list', ['GET'], '/resources', static fn (): array => [], 'resources.read', true));
        $routes->add(new Route('resources.create', ['POST'], '/resources', static fn (): array => [], 'resources.create', false));

        $registry = new ObjectRegistry();
        $registry->register(
            ObjectDefinition::create('resources')
                ->setName('resources')
                ->class(\xPDOObject::class)
                ->fields(['id'])
                ->readable(),
        );

        $spec = (new OpenApiGenerator($routes, $registry, new ApiPrefix(new modX())))->generate();

        self::assertSame('3.0.3', $spec['openapi']);
        self::assertSame(\MxHeadless\Version::STRING, $spec['info']['version']);
        self::assertArrayHasKey('/resources', $spec['paths']);
        self::assertArrayHasKey('/meta/openapi', $spec['paths']);
        self::assertSame([], $spec['paths']['/meta/openapi']['get']['security']);
        self::assertSame([['bearerAuth' => []]], $spec['paths']['/resources']['post']['security']);
        self::assertContains('resources', $spec['x-mxheadless']['objects']);
        self::assertArrayHasKey('code', $spec['components']['schemas']['ProblemDetails']['properties']);
    }

    public function testOpenApiUsesRouteMetadata(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(
            'pages.get',
            ['GET'],
            '/pages/{uri}',
            static fn (): array => [],
            'pages.read',
            true,
            [],
            null,
            'pages',
            'Resolve a page by URI alias',
            ['Pages'],
            [
                new RouteParameter('uri', 'path', true, 'Page alias path'),
                new RouteParameter('context', 'query', false, 'MODX context key', ['type' => 'string']),
            ],
        ));

        $registry = new ObjectRegistry();
        $spec = (new OpenApiGenerator($routes, $registry, new ApiPrefix(new modX())))->generate();

        $operation = $spec['paths']['/pages/{uri}']['get'];
        self::assertSame('Resolve a page by URI alias', $operation['summary']);
        self::assertSame(['Pages'], $operation['tags']);
        self::assertCount(2, $operation['parameters']);
        self::assertSame('context', $operation['parameters'][1]['name']);
    }

    public function testEndpointCatalogIncludesMetadata(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(
            'custom.action',
            ['POST'],
            '/custom',
            static fn (): array => [],
            'custom.write',
            false,
            [],
            null,
            null,
            'Custom action',
            ['Custom'],
            [new RouteParameter('id', 'path')],
        ));

        $payload = (new EndpointCatalogService($routes))->handle();
        $entry = $payload['data']['endpoints'][0];

        self::assertSame('Custom action', $entry['description']);
        self::assertSame(['Custom'], $entry['tags']);
        self::assertSame('id', $entry['parameters'][0]['name']);
    }
}
