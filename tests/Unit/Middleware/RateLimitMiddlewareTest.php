<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use MxHeadless\Middleware\RateLimitMiddleware;
use MxHeadless\RateLimit\RateLimitDecision;
use MxHeadless\RateLimit\RateLimiterInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddlewareTest extends TestCase
{
    public function testUsesGlobalDefaultsWhenKeyHasNoOverride(): void
    {
        $limiter = new RecordingRateLimiter(true);
        $middleware = new RateLimitMiddleware(
            new modX([
                'mxheadless_rate_limit_enabled' => true,
                'mxheadless_rate_limit_max_requests' => 120,
                'mxheadless_rate_limit_window_seconds' => 60,
            ]),
            $limiter,
        );

        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity_key', 'anonymous')
            ->withAttribute('client_ip', '127.0.0.1');

        $middleware->process($request, $this->okHandler());

        self::assertSame(120, $limiter->lastLimit);
        self::assertSame(60, $limiter->lastWindow);
    }

    public function testUsesPerKeyLimitsWhenPresent(): void
    {
        $limiter = new RecordingRateLimiter(true);
        $middleware = new RateLimitMiddleware(
            new modX([
                'mxheadless_rate_limit_enabled' => true,
                'mxheadless_rate_limit_max_requests' => 120,
                'mxheadless_rate_limit_window_seconds' => 60,
            ]),
            $limiter,
        );

        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity_key', 'api_key:abc')
            ->withAttribute('client_ip', '127.0.0.1')
            ->withAttribute('rate_limit_max', 30)
            ->withAttribute('rate_limit_window', 15);

        $middleware->process($request, $this->okHandler());

        self::assertSame(30, $limiter->lastLimit);
        self::assertSame(15, $limiter->lastWindow);
    }

    public function testAddsRateLimitHeadersOnSuccess(): void
    {
        $middleware = new RateLimitMiddleware(
            new modX([
                'mxheadless_rate_limit_enabled' => true,
                'mxheadless_rate_limit_max_requests' => 120,
                'mxheadless_rate_limit_window_seconds' => 60,
            ]),
            new RecordingRateLimiter(true),
        );

        $response = $middleware->process(
            (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
                ->withAttribute('identity_key', 'anonymous')
                ->withAttribute('client_ip', '127.0.0.1'),
            $this->okHandler(),
        );

        self::assertSame('120', $response->getHeaderLine('X-RateLimit-Limit'));
        self::assertSame('119', $response->getHeaderLine('X-RateLimit-Remaining'));
        self::assertNotSame('', $response->getHeaderLine('X-RateLimit-Reset'));
    }

    public function testThrowsWhenLimitExceeded(): void
    {
        $middleware = new RateLimitMiddleware(
            new modX(['mxheadless_rate_limit_enabled' => true]),
            new RecordingRateLimiter(false),
        );

        try {
            $middleware->process(
                (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
                    ->withAttribute('identity_key', 'anonymous')
                    ->withAttribute('client_ip', '127.0.0.1'),
                $this->okHandler(),
            );
            self::fail('Expected HttpException');
        } catch (HttpException $e) {
            self::assertSame(429, $e->getStatusCode());
            self::assertSame('Rate limit exceeded', $e->getMessage());
            $headers = $e->getHeaders();
            self::assertSame('0', $headers['X-RateLimit-Remaining'] ?? null);
            self::assertArrayHasKey('Retry-After', $headers);
        }
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], '{}');
            }
        };
    }
}

final class RecordingRateLimiter implements RateLimiterInterface
{
    public ?int $lastLimit = null;
    public ?int $lastWindow = null;

    public function __construct(
        private readonly bool $allow,
    ) {
    }

    public function attempt(string $key, int $limit, int $windowSeconds): RateLimitDecision
    {
        $this->lastLimit = $limit;
        $this->lastWindow = $windowSeconds;

        return new RateLimitDecision(
            $this->allow,
            $limit,
            $this->allow ? max(0, $limit - 1) : 0,
            time() + $windowSeconds,
        );
    }
}
