<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Http\RequestLifecycle;
use MxHeadless\Middleware\RequestLifecycleMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestLifecycleMiddlewareTest extends TestCase
{
    public function testBeforeRequestCanReplaceRequest(): void
    {
        $replacement = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('X-Test', 'replaced');
        $modx = new class ($replacement) extends modX {
            public function __construct(private readonly ServerRequestInterface $replacement)
            {
                parent::__construct();
            }

            public function invokeEvent(string $eventName, array $params = []): void
            {
                parent::invokeEvent($eventName, $params);
                if ($eventName === 'OnMxHeadlessBeforeRequest') {
                    $this->Event->returned['request'] = $this->replacement;
                }
            }
        };

        $request = RequestLifecycle::before($modx, new ServerRequest('GET', 'https://example.test/api/v1/health'));
        self::assertSame('replaced', $request->getHeaderLine('X-Test'));
    }

    public function testMiddlewareAppliesLifecycleEvents(): void
    {
        $modx = new LifecycleModX();
        $middleware = new RequestLifecycleMiddleware($modx);
        $response = $middleware->process(new ServerRequest('GET', 'https://example.test/api/v1/health'), new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(204);
            }
        });

        self::assertSame(['OnMxHeadlessBeforeRequest', 'OnMxHeadlessAfterRequest'], $modx->events);
        self::assertSame(204, $response->getStatusCode());
    }
}

final class LifecycleModX extends modX
{
    /** @var list<string> */
    public array $events = [];

    public function invokeEvent(string $eventName, array $params = []): void
    {
        $this->events[] = $eventName;
        parent::invokeEvent($eventName, $params);
    }
}
