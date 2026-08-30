<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Authentication\AnonymousPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Exception\ForbiddenException;
use MxHeadless\Middleware\CsrfMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['mxheadless.csrf_token']);
        parent::tearDown();
    }

    public function testPassesWhenCsrfDisabled(): void
    {
        $middleware = new CsrfMiddleware(new modX(['mxheadless.csrf.enabled' => false]));
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $this->sessionIdentity());

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testGetAlwaysPasses(): void
    {
        $middleware = new CsrfMiddleware(new modX(['mxheadless.csrf.enabled' => true]));
        $request = (new ServerRequest('GET', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $this->sessionIdentity());

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testApiKeyIdentityPostPassesWithoutCsrf(): void
    {
        $middleware = new CsrfMiddleware(new modX(['mxheadless.csrf.enabled' => true]));
        $identity = new Identity(Identity::TYPE_API_KEY, 'api_key:abc', new AnonymousPermissionChecker());
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $identity);

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testSessionIdentityPostWithoutTokenThrowsForbidden(): void
    {
        $middleware = new CsrfMiddleware(new modX(['mxheadless.csrf.enabled' => true]));
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $this->sessionIdentity());

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Invalid CSRF token');
        $middleware->process($request, $this->okHandler());
    }

    public function testSessionIdentityPostWithMatchingTokenPasses(): void
    {
        $_SESSION['mxheadless.csrf_token'] = 'valid-csrf-token';
        $middleware = new CsrfMiddleware(new modX(['mxheadless.csrf.enabled' => true]));
        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $this->sessionIdentity())
            ->withHeader('X-CSRF-Token', 'valid-csrf-token');

        $response = $middleware->process($request, $this->okHandler());
        self::assertSame(200, $response->getStatusCode());
    }

    private function sessionIdentity(): Identity
    {
        return new Identity(Identity::TYPE_SESSION, 'session:user-1', new AnonymousPermissionChecker(), 1);
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
