<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;
use MxHeadless\Audit\AuditLogWriter;
use MxHeadless\Authentication\AuthenticatorChain;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Idempotency\IdempotencyStore;
use MxHeadless\Middleware\AuditLogMiddleware;
use MxHeadless\Middleware\AuthenticationMiddleware;
use MxHeadless\Middleware\AuthorizationMiddleware;
use MxHeadless\Middleware\BodyLimitMiddleware;
use MxHeadless\Middleware\ContentNegotiationMiddleware;
use MxHeadless\Middleware\CorsMiddleware;
use MxHeadless\Middleware\CsrfMiddleware;
use MxHeadless\Middleware\ErrorMiddleware;
use MxHeadless\Middleware\HttpCacheMiddleware;
use MxHeadless\Middleware\IdempotencyMiddleware;
use MxHeadless\Middleware\RateLimitMiddleware;
use MxHeadless\Middleware\RequestLifecycleMiddleware;
use MxHeadless\Middleware\RequestIdMiddleware;
use MxHeadless\Middleware\RoutingMiddleware;
use MxHeadless\Middleware\ServiceGateMiddleware;
use MxHeadless\Middleware\TrustedProxyMiddleware;
use MxHeadless\RateLimit\RateLimiterInterface;
use MxHeadless\Routing\Router;

final class MiddlewareStackBuilder
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    /**
     * @return list<\Psr\Http\Server\MiddlewareInterface>
     */
    public function build(
        Router $router,
        ApiPrefix $apiPrefix,
        AuthenticatorChain $authChain,
        Authorizer $authorizer,
        RateLimiterInterface $rateLimiter,
        IdempotencyStore $idempotencyStore,
        ModxCacheAdapter $cache,
        AuditLogWriter $auditLog,
    ): array {
        $registrar = new MiddlewareRegistrar([
            new AuditLogMiddleware($this->modx, $auditLog),
            new ErrorMiddleware($this->modx),
            new TrustedProxyMiddleware($this->modx),
            new RequestIdMiddleware(),
            new BodyLimitMiddleware($this->modx),
            new ContentNegotiationMiddleware(),
            new CorsMiddleware($this->modx),
            new RoutingMiddleware($router),
            new ServiceGateMiddleware($this->modx, $apiPrefix),
            new AuthenticationMiddleware($authChain),
            new RateLimitMiddleware($this->modx, $rateLimiter),
            new CsrfMiddleware($this->modx),
            new AuthorizationMiddleware($authorizer),
            new RequestLifecycleMiddleware($this->modx),
            new IdempotencyMiddleware($idempotencyStore),
            new HttpCacheMiddleware($this->modx, $cache),
        ]);

        $this->modx->invokeEvent('OnMxHeadlessRegisterMiddleware', ['registrar' => $registrar]);

        return $registrar->all();
    }
}
