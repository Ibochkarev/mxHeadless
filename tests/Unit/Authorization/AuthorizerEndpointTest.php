<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Authorization;

use MxHeadless\Authentication\ApiKeyPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Exception\ForbiddenException;
use MxHeadless\Exception\UnauthorizedException;
use Nyholm\Psr7\ServerRequest;
use MxHeadless\Routing\Route;
use MODX\Revolution\modX;
use PHPUnit\Framework\TestCase;

final class AuthorizerEndpointTest extends TestCase
{
    public function testPublicGetWithPermissionRequiresScopeForAuthenticatedApiKey(): void
    {
        $authorizer = new Authorizer(new modX());
        $route = new Route(
            'objects.list',
            ['GET'],
            '/objects/{name}',
            static fn (): array => ['data' => [], 'meta' => []],
            null,
            true,
            [],
            static fn (array $params): string => $params['name'] . '.read',
        );

        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['other.read']),
        );

        $request = (new ServerRequest('GET', '/api/v1/objects/products'))
            ->withAttribute('identity', $identity)
            ->withAttribute('route', $route)
            ->withAttribute('route_params', ['name' => 'products']);

        $this->expectException(ForbiddenException::class);
        $authorizer->authorizeEndpoint($request, $route);
    }

    public function testPublicHeadWithPermissionAllowsAnonymous(): void
    {
        $authorizer = new Authorizer(new modX());
        $route = new Route(
            'pages.get',
            ['GET', 'HEAD'],
            '/pages/{uri}',
            static fn (): array => ['data' => []],
            'resources.read',
            true,
        );

        $request = (new ServerRequest('HEAD', '/api/v1/pages/index'))
            ->withAttribute('identity', Identity::anonymous())
            ->withAttribute('route', $route)
            ->withAttribute('route_params', ['uri' => 'index']);

        $authorizer->authorizeEndpoint($request, $route);
        $this->addToAssertionCount(1);
    }

    public function testNonPublicObjectsRequireAuthentication(): void
    {
        $authorizer = new Authorizer(new modX());
        $route = new Route(
            'objects.list',
            ['GET'],
            '/objects/{name}',
            static fn (): array => ['data' => [], 'meta' => []],
            null,
            false,
            [],
            static fn (array $params): string => $params['name'] . '.read',
        );

        $request = (new ServerRequest('GET', '/api/v1/objects/products'))
            ->withAttribute('identity', Identity::anonymous())
            ->withAttribute('route', $route)
            ->withAttribute('route_params', ['name' => 'products']);

        try {
            $authorizer->authorizeEndpoint($request, $route);
            self::fail('Expected UnauthorizedException');
        } catch (UnauthorizedException $e) {
            self::assertSame(UnauthorizedException::CODE_TOKEN_REQUIRED, $e->getErrorCode());
        }
    }

    public function testInvalidBearerReturnsInvalidTokenCode(): void
    {
        $authorizer = new Authorizer(new modX());
        $route = new Route(
            'objects.list',
            ['GET'],
            '/objects/{name}',
            static fn (): array => ['data' => [], 'meta' => []],
            null,
            false,
            [],
            static fn (array $params): string => $params['name'] . '.read',
        );

        $request = (new ServerRequest('GET', '/api/v1/objects/products'))
            ->withHeader('Authorization', 'Bearer mxh_deadbeef_deadbeefdeadbeefdeadbeef')
            ->withAttribute('identity', Identity::anonymous())
            ->withAttribute('route', $route)
            ->withAttribute('route_params', ['name' => 'products']);

        try {
            $authorizer->authorizeEndpoint($request, $route);
            self::fail('Expected UnauthorizedException');
        } catch (UnauthorizedException $e) {
            self::assertSame(UnauthorizedException::CODE_INVALID_TOKEN, $e->getErrorCode());
        }
    }

    public function testUnknownRegisteredObjectNameReturnsNotFound(): void
    {
        $registry = new \MxHeadless\Registry\ObjectRegistry();
        $authorizer = new Authorizer(new modX(), $registry);
        $route = new Route(
            'objects.list',
            ['GET'],
            '/objects/{name}',
            static fn (): array => ['data' => [], 'meta' => []],
            null,
            false,
            [],
            static fn (array $params): string => $params['name'] . '.read',
        );

        $identity = new Identity(
            Identity::TYPE_API_KEY,
            'mxh_test',
            new ApiKeyPermissionChecker(['nope.read']),
        );

        $request = (new ServerRequest('GET', '/api/v1/objects/nope'))
            ->withAttribute('identity', $identity)
            ->withAttribute('route', $route)
            ->withAttribute('route_params', ['name' => 'nope']);

        $this->expectException(\MxHeadless\Exception\NotFoundException::class);
        $authorizer->authorizeEndpoint($request, $route);
    }
}
