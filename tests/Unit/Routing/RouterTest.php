<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Routing;

use MODX\Revolution\modX;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Routing\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testPagesUriKeepsNestedSlashes(): void
    {
        $match = $this->router()->match(
            new ServerRequest('GET', 'https://example.test/api/v1/pages/catalog/bod/red.html'),
        );

        self::assertNotNull($match);
        self::assertSame('catalog/bod/red.html', $match['params']['uri']);
    }

    public function testPagesUriDecodesPercentEncoding(): void
    {
        $match = $this->router()->match(
            new ServerRequest('GET', 'https://example.test/api/v1/pages/catalog%2Fbod%2Fred.html'),
        );

        self::assertNotNull($match);
        self::assertSame('catalog/bod/red.html', $match['params']['uri']);
    }

    private function router(): Router
    {
        $routes = new RouteCollection();
        $routes->add(new Route('pages.get', ['GET'], '/pages/{uri}', static fn (): array => []));

        return new Router($routes, new ApiPrefix(new modX()));
    }
}
