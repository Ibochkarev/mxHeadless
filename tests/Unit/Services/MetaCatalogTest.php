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
        $routes->add(new Route(
            'resources.list',
            ['GET'],
            '/resources',
            static fn (): array => [],
            'resources.read',
            true,
            [],
            null,
            'resources',
        ));
        $routes->add(new Route('resources.create', ['POST'], '/resources', static fn (): array => [], 'resources.create', false));

        $registry = new ObjectRegistry();
        $registry->register(
            ObjectDefinition::create('resources')
                ->setName('resources')
                ->class(\xPDOObject::class)
                ->fields(['id', 'pagetitle', 'published', 'deleted', 'parent'])
                ->filterable(['id', 'parent', 'published', 'deleted'])
                ->sorts(['id', 'pagetitle'])
                ->readable(),
        );

        $spec = (new OpenApiGenerator($routes, $registry, new ApiPrefix(new modX())))->generate();

        self::assertSame('3.0.3', $spec['openapi']);
        self::assertSame(\MxHeadless\Version::STRING, $spec['info']['version']);
        self::assertArrayHasKey('/resources', $spec['paths']);
        self::assertArrayHasKey('/meta/openapi', $spec['paths']);
        self::assertEquals(
            [(object) [], ['bearerAuth' => []]],
            $spec['paths']['/meta/openapi']['get']['security'],
        );
        self::assertSame([['bearerAuth' => []]], $spec['paths']['/resources']['post']['security']);
        self::assertArrayHasKey('requestBody', $spec['paths']['/resources']['post']);
        self::assertArrayHasKey('201', $spec['paths']['/resources']['post']['responses']);
        self::assertContains('resources', $spec['x-mxheadless']['objects']);
        self::assertArrayHasKey('code', $spec['components']['schemas']['ProblemDetails']['properties']);
        self::assertArrayHasKey('MutationBody', $spec['components']['schemas']);

        $listParams = array_column($spec['paths']['/resources']['get']['parameters'], 'name');
        self::assertContains('limit', $listParams);
        self::assertContains('filter[id][eq]', $listParams);
        self::assertContains('filter[parent][eq]', $listParams);
        self::assertContains('sort', $listParams);
        self::assertContains('preview', $listParams);
        self::assertContains('include_deleted', $listParams);
        self::assertNotContains('filter[published][eq]', $listParams);
        self::assertContains('Resources', array_column($spec['tags'], 'name'));
    }

    public function testOpenApiDeleteAndItemSoftDeleteParams(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(
            'resources.get',
            ['GET'],
            '/resources/{id}',
            static fn (): array => [],
            'resources.read',
            true,
            [],
            null,
            'resources',
        ));
        $routes->add(new Route(
            'resources.delete',
            ['DELETE'],
            '/resources/{id}',
            static fn (): array => [],
            'resources.delete',
            false,
            [],
            null,
            'resources',
        ));
        $routes->add(new Route(
            'templates.get',
            ['GET'],
            '/templates/{id}',
            static fn (): array => [],
            'templates.read',
            false,
            [],
            null,
            'templates',
        ));

        $registry = new ObjectRegistry();
        $registry->register(
            ObjectDefinition::create('resources')
                ->class(\xPDOObject::class)
                ->fields(['id', 'pagetitle', 'published', 'deleted'])
                ->filterable(['id', 'published', 'deleted'])
                ->readable()
                ->deletable(),
        );
        $registry->register(
            ObjectDefinition::create('templates')
                ->class(\xPDOObject::class)
                ->fields(['id', 'templatename'])
                ->readable(),
        );

        $spec = (new OpenApiGenerator($routes, $registry, new ApiPrefix(new modX())))->generate();

        $getParams = array_column($spec['paths']['/resources/{id}']['get']['parameters'], 'name');
        self::assertContains('include_deleted', $getParams);
        self::assertContains('preview', $getParams);

        $deleteParams = array_column($spec['paths']['/resources/{id}']['delete']['parameters'], 'name');
        self::assertContains('force', $deleteParams);

        $templateGetParams = array_column($spec['paths']['/templates/{id}']['get']['parameters'], 'name');
        self::assertNotContains('include_deleted', $templateGetParams);
    }

    public function testOpenApiOmitsIncludeWithoutRelations(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(
            'content_types.list',
            ['GET'],
            '/content_types',
            static fn (): array => [],
            'content_types.read',
            false,
            [],
            null,
            'content_types',
        ));
        $routes->add(new Route(
            'templates.list',
            ['GET'],
            '/templates',
            static fn (): array => [],
            'templates.read',
            false,
            [],
            null,
            'templates',
        ));

        $registry = new ObjectRegistry();
        $registry->register(
            ObjectDefinition::create('content_types')
                ->class(\xPDOObject::class)
                ->fields(['id', 'name', 'mime_type'])
                ->filterable(['name', 'mime_type'])
                ->sorts(['id', 'name'])
                ->readable(),
        );
        $registry->register(
            ObjectDefinition::create('templates')
                ->class(\xPDOObject::class)
                ->fields(['id', 'templatename', 'category'])
                ->filterable(['templatename', 'category'])
                ->sorts(['id', 'templatename'])
                ->readable()
                ->relation(
                    \MxHeadless\Definition\RelationDefinition::create('category')
                        ->to('categories')
                        ->toOne()
                        ->foreignKeyField('category')
                        ->localKeyField('id'),
                ),
        );
        $registry->register(
            ObjectDefinition::create('categories')
                ->class(\xPDOObject::class)
                ->fields(['id', 'category'])
                ->readable(),
        );

        $spec = (new OpenApiGenerator($routes, $registry, new ApiPrefix(new modX())))->generate();

        $contentTypeParams = array_column($spec['paths']['/content_types']['get']['parameters'], 'name');
        self::assertNotContains('include', $contentTypeParams);
        self::assertNotContains('preview', $contentTypeParams);

        $templateParams = array_column($spec['paths']['/templates']['get']['parameters'], 'name');
        self::assertContains('include', $templateParams);
        $include = null;
        foreach ($spec['paths']['/templates']['get']['parameters'] as $parameter) {
            if (($parameter['name'] ?? '') === 'include') {
                $include = $parameter;
                break;
            }
        }
        self::assertSame('category', $include['schema']['example'] ?? null);
    }

    public function testOpenApiHandleRawReturnsDocumentWithoutEnvelope(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route('meta.openapi.json', ['GET'], '/meta/openapi.json', static fn (): array => [], null, true));

        $registry = new ObjectRegistry();
        $generator = new OpenApiGenerator($routes, $registry, new ApiPrefix(new modX()));
        $response = $generator->handleRaw();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/openapi+json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame('3.0.3', $body['openapi']);
        self::assertArrayNotHasKey('data', $body);
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
        $paramNames = array_column($operation['parameters'], 'name');
        self::assertContains('uri', $paramNames);
        self::assertContains('context', $paramNames);
        self::assertContains('fields', $paramNames);
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
