<?php

declare(strict_types=1);

namespace MxHeadless\Routing;

use MxHeadless\Services\DiscoveryService;
use MxHeadless\Services\EndpointCatalogService;
use MxHeadless\Services\HealthService;
use MxHeadless\Services\ObjectService;
use MxHeadless\Services\OpenApiGenerator;
use MxHeadless\Services\SchemaService;
use MxHeadless\Services\TokenService;
use Psr\Http\Message\ServerRequestInterface;

final class RoutesRegistrar
{
    public function __construct(
        private readonly DiscoveryService $discovery,
        private readonly HealthService $health,
        private readonly SchemaService $schema,
        private readonly ObjectService $objects,
        private readonly EndpointCatalogService $endpoints,
        private readonly OpenApiGenerator $openApi,
        private readonly TokenService $tokens,
    ) {
    }

    public function register(RouteCollection $routes): void
    {
        $routes->add(new Route('discovery', ['GET'], '/', fn () => $this->discovery->handle(), null, true));
        $routes->add(new Route('health', ['GET'], '/health', fn () => $this->health->handle(), null, true));
        $routes->add(new Route('schema', ['GET'], '/schema', fn () => $this->schema->handle(), null, true));
        $routes->add(new Route(
            'auth.token',
            ['POST'],
            '/auth/token',
            fn (ServerRequestInterface $request): array => $this->tokens->handle($request),
            null,
            true,
            [],
            null,
            null,
            'Issue a short-lived OAuth access token',
            ['Auth'],
        ));
        $routes->add(new Route(
            'meta.endpoints',
            ['GET'],
            '/meta/endpoints',
            fn () => $this->endpoints->handle(),
            null,
            true,
        ));
        $routes->add(new Route(
            'meta.openapi',
            ['GET'],
            '/meta/openapi',
            fn () => $this->openApi->handle(),
            null,
            true,
        ));

        $this->registerCrud(
            $routes,
            'resources',
            '/resources',
            'resources',
            true,
            'resources.read',
            'resources.create',
            'resources.update',
            'resources.delete',
        );

        $this->registerReadOnly(
            $routes,
            'contexts',
            '/contexts',
            'contexts',
            'contexts.read',
        );

        $this->registerReadOnly(
            $routes,
            'chunks',
            '/chunks',
            'chunks',
            'chunks.read',
        );

        $routes->add(new Route(
            'pages.get',
            ['GET'],
            '/pages/{uri}',
            fn (ServerRequestInterface $request, array $params) => $this->objects->getByField(
                $request,
                'resources',
                'uri',
                (string) $params['uri'],
            ),
            'resources.read',
            true,
            [],
            null,
            'resources',
        ));

        $objectPermission = static fn (array $params, string $action): string => $params['name'] . '.' . $action;

        $routes->add(new Route(
            'objects.list',
            ['GET'],
            '/objects/{name}',
            fn ($request, $params) => $this->objects->list($request, (string) $params['name']),
            null,
            false,
            [],
            static fn (array $params): string => $objectPermission($params, 'read'),
        ));

        $routes->add(new Route(
            'objects.get',
            ['GET'],
            '/objects/{name}/{id}',
            fn ($request, $params) => $this->objects->get($request, (string) $params['name'], (string) $params['id']),
            null,
            false,
            [],
            static fn (array $params): string => $objectPermission($params, 'read'),
        ));

        $routes->add(new Route(
            'objects.create',
            ['POST'],
            '/objects/{name}',
            fn ($request, $params) => $this->objects->create($request, (string) $params['name']),
            null,
            false,
            [],
            static fn (array $params): string => $objectPermission($params, 'create'),
        ));

        $routes->add(new Route(
            'objects.mutate',
            ['PUT', 'PATCH'],
            '/objects/{name}/{id}',
            fn ($request, $params) => $this->objects->update($request, (string) $params['name'], (string) $params['id']),
            null,
            false,
            [],
            static fn (array $params): string => $objectPermission($params, 'update'),
        ));

        $routes->add(new Route(
            'objects.delete',
            ['DELETE'],
            '/objects/{name}/{id}',
            fn ($request, $params) => $this->objects->delete($request, (string) $params['name'], (string) $params['id']),
            null,
            false,
            [],
            static fn (array $params): string => $objectPermission($params, 'delete'),
        ));
    }

    private function registerReadOnly(
        RouteCollection $routes,
        string $prefix,
        string $path,
        string $objectName,
        string $readPermission,
    ): void {
        $routes->add(new Route(
            $prefix . '.list',
            ['GET'],
            $path,
            fn ($request) => $this->objects->list($request, $objectName),
            $readPermission,
            false,
            [],
            null,
            $objectName,
        ));

        $routes->add(new Route(
            $prefix . '.get',
            ['GET'],
            $path . '/{id}',
            fn ($request, $params) => $this->objects->get($request, $objectName, (string) $params['id']),
            $readPermission,
            false,
            [],
            null,
            $objectName,
        ));
    }

    private function registerCrud(
        RouteCollection $routes,
        string $prefix,
        string $path,
        string $objectName,
        bool $publicRead,
        string $readPermission,
        string $createPermission,
        string $updatePermission,
        string $deletePermission,
    ): void {
        $routes->add(new Route(
            $prefix . '.list',
            ['GET'],
            $path,
            fn ($request) => $this->objects->list($request, $objectName),
            $readPermission,
            $publicRead,
            [],
            null,
            $objectName,
        ));

        $routes->add(new Route(
            $prefix . '.get',
            ['GET'],
            $path . '/{id}',
            fn ($request, $params) => $this->objects->get($request, $objectName, (string) $params['id']),
            $readPermission,
            $publicRead,
            [],
            null,
            $objectName,
        ));

        $routes->add(new Route(
            $prefix . '.create',
            ['POST'],
            $path,
            fn ($request) => $this->objects->create($request, $objectName),
            $createPermission,
        ));

        $routes->add(new Route(
            $prefix . '.mutate',
            ['PUT', 'PATCH'],
            $path . '/{id}',
            fn ($request, $params) => $this->objects->update($request, $objectName, (string) $params['id']),
            $updatePermission,
        ));

        $routes->add(new Route(
            $prefix . '.delete',
            ['DELETE'],
            $path . '/{id}',
            fn ($request, $params) => $this->objects->delete($request, $objectName, (string) $params['id']),
            $deletePermission,
        ));
    }
}
