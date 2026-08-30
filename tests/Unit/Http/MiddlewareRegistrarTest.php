<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Http;

use MxHeadless\Http\MiddlewareRegistrar;
use MxHeadless\Middleware\AuditLogMiddleware;
use MxHeadless\Middleware\AuthenticationMiddleware;
use MxHeadless\Middleware\AuthorizationMiddleware;
use MxHeadless\Middleware\RequestLifecycleMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareRegistrarTest extends TestCase
{
    public function testPrependAndAppendChangeOrder(): void
    {
        $first = new StubMiddleware('first');
        $second = new StubMiddleware('second');
        $registrar = new MiddlewareRegistrar([$second]);

        $registrar->prepend($first);
        $registrar->append(new StubMiddleware('third'));

        self::assertSame(['first', 'second', 'third'], $this->names($registrar->all()));
    }

    /**
     * @param list<MiddlewareInterface> $middleware
     * @return list<string>
     */
    private function names(array $middleware): array
    {
        return array_map(
            static fn (MiddlewareInterface $middleware): string => $middleware instanceof StubMiddleware
                ? $middleware->name
                : $middleware::class,
            $middleware,
        );
    }
}

final class StubMiddleware implements MiddlewareInterface
{
    public function __construct(public readonly string $name)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

final class MiddlewareOrderTest extends TestCase
{
    public function testLifecycleMiddlewareRunsAfterAuthorization(): void
    {
        $classes = [
            AuditLogMiddleware::class,
            AuthenticationMiddleware::class,
            AuthorizationMiddleware::class,
            RequestLifecycleMiddleware::class,
        ];

        $authIndex = array_search(AuthorizationMiddleware::class, $classes, true);
        $lifecycleIndex = array_search(RequestLifecycleMiddleware::class, $classes, true);

        self::assertNotFalse($authIndex);
        self::assertNotFalse($lifecycleIndex);
        self::assertLessThan($lifecycleIndex, $authIndex);
    }
}
