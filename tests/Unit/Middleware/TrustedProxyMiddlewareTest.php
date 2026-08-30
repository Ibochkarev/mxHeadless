<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Middleware\TrustedProxyMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TrustedProxyMiddlewareTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $savedServer;

    protected function setUp(): void
    {
        $this->savedServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;
        parent::tearDown();
    }

    public function testNoTrustedProxiesUsesRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $middleware = new TrustedProxyMiddleware(new modX(['mxheadless_trusted_proxies' => '']));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('X-Forwarded-For', '198.51.100.5, 203.0.113.1');

        $clientIp = null;
        $middleware->process($request, $this->capturingHandler($clientIp));

        self::assertSame('203.0.113.10', $clientIp);
    }

    public function testTrustedProxyUsesFirstForwardedForIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $middleware = new TrustedProxyMiddleware(new modX(['mxheadless_trusted_proxies' => '10.0.0.1']));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('X-Forwarded-For', '198.51.100.5, 203.0.113.1');

        $clientIp = null;
        $middleware->process($request, $this->capturingHandler($clientIp));

        self::assertSame('198.51.100.5', $clientIp);
    }

    public function testUntrustedProxyIgnoresForwardedFor(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $middleware = new TrustedProxyMiddleware(new modX(['mxheadless_trusted_proxies' => '10.0.0.1']));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withHeader('X-Forwarded-For', '198.51.100.5');

        $clientIp = null;
        $middleware->process($request, $this->capturingHandler($clientIp));

        self::assertSame('203.0.113.10', $clientIp);
    }

    private function capturingHandler(?string &$clientIp): RequestHandlerInterface
    {
        return new class($clientIp) implements RequestHandlerInterface {
            public function __construct(private ?string &$clientIp)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->clientIp = $request->getAttribute('client_ip');

                return new Response(200, [], '{}');
            }
        };
    }
}
