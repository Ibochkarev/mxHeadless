<?php

declare(strict_types=1);

namespace MxHeadless;

use MODX\Revolution\modX;
use MxHeadless\Audit\AuditLogRepository;
use MxHeadless\Authentication\ApiKeyAuthenticator;
use MxHeadless\Authentication\ApiKeyRepository;
use MxHeadless\Authentication\AuthenticatorChain;
use MxHeadless\Authentication\OAuthClientRepository;
use MxHeadless\Authentication\OAuthTokenAuthenticator;
use MxHeadless\Authentication\OAuthTokenRepository;
use MxHeadless\Authentication\SessionAuthenticator;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Extension\ExtensionApi;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Http\MiddlewarePipeline;
use MxHeadless\Http\MiddlewareStackBuilder;
use MxHeadless\Http\Psr7RequestFactory;
use MxHeadless\Http\ResponseEmitter;
use MxHeadless\Idempotency\IdempotencyStore;
use MxHeadless\Media\MediaUrlResolver;
use MxHeadless\Query\QueryParser;
use MxHeadless\Query\VisibilityPolicy;
use MxHeadless\Query\XpdoQueryCompiler;
use MxHeadless\RateLimit\FileRateLimiter;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Routing\RouteDispatcher;
use MxHeadless\Routing\Router;
use MxHeadless\Routing\RoutesRegistrar;
use MxHeadless\Serialization\RelationLoader;
use MxHeadless\Serialization\XpdoObjectSerializer;
use MxHeadless\Services\DiscoveryService;
use MxHeadless\Services\EndpointCatalogService;
use MxHeadless\Services\HealthService;
use MxHeadless\Services\MutationHooks;
use MxHeadless\Services\ObjectService;
use MxHeadless\Services\OpenApiGenerator;
use MxHeadless\Services\SchemaService;
use MxHeadless\Services\TokenService;
use MxHeadless\Tv\ModxTvProvider;
use MxHeadless\Webhook\WebhookDispatcher;
use MxHeadless\Webhook\WebhookOutbox;
use MxHeadless\Webhook\WebhookSigner;

final class ApplicationFactory
{
    private ?Application $application = null;

    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function create(): Application
    {
        if ($this->application !== null) {
            return $this->application;
        }

        $registry = new ObjectRegistry();
        $routes = new RouteCollection();
        $apiPrefix = new ApiPrefix($this->modx);
        $router = new Router($routes, $apiPrefix);
        $authorizer = new Authorizer($this->modx, $registry);
        $visibility = new VisibilityPolicy($authorizer);

        $mediaResolver = new MediaUrlResolver($this->modx);
        $serializer = new XpdoObjectSerializer($mediaResolver);
        $relationLoader = new RelationLoader($this->modx, $registry, $serializer, $visibility);
        $cache = new ModxCacheAdapter($this->modx);
        $rateLimiter = new FileRateLimiter($this->modx);
        $apiKeyRepo = new ApiKeyRepository($this->modx);
        $oauthClientRepo = new OAuthClientRepository($this->modx);
        $oauthTokenRepo = new OAuthTokenRepository($this->modx);
        $tvProvider = new ModxTvProvider();
        $queryParser = new QueryParser($this->modx);
        $queryCompiler = new XpdoQueryCompiler($this->modx, $visibility);

        $webhookOutbox = new WebhookOutbox($this->modx);
        $webhookDispatcher = new WebhookDispatcher($this->modx, $webhookOutbox, new WebhookSigner());
        $mutations = new MutationHooks($cache, $webhookOutbox);
        $idempotencyStore = new IdempotencyStore($this->modx, $cache);
        $auditLog = new AuditLogRepository($this->modx);

        $objectService = new ObjectService(
            $this->modx,
            $registry,
            $authorizer,
            $serializer,
            $queryParser,
            $queryCompiler,
            $relationLoader,
            $visibility,
            $mutations,
            $tvProvider,
        );

        $discoveryService = new DiscoveryService($apiPrefix, $this->modx);
        $healthService = new HealthService($this->modx);
        $schemaService = new SchemaService($registry);
        $endpointCatalog = new EndpointCatalogService($routes);
        $openApiGenerator = new OpenApiGenerator($routes, $registry, $apiPrefix);
        $tokenService = new TokenService($this->modx, $oauthClientRepo, $oauthTokenRepo);

        $routesRegistrar = new RoutesRegistrar(
            $discoveryService,
            $healthService,
            $schemaService,
            $objectService,
            $endpointCatalog,
            $openApiGenerator,
            $tokenService,
        );
        $routesRegistrar->register($routes);

        $dispatcher = new RouteDispatcher();
        $extensionApi = new ExtensionApi($registry, $routes);

        $authChain = new AuthenticatorChain([
            new OAuthTokenAuthenticator($oauthTokenRepo, $oauthClientRepo, $this->modx),
            new ApiKeyAuthenticator($apiKeyRepo, $this->modx),
            new SessionAuthenticator($this->modx),
        ]);

        $middlewareStack = (new MiddlewareStackBuilder($this->modx))->build(
            $router,
            $apiPrefix,
            $authChain,
            $authorizer,
            $rateLimiter,
            $idempotencyStore,
            $cache,
            $auditLog,
        );

        $pipeline = new MiddlewarePipeline($dispatcher, $middlewareStack);

        $this->application = new Application(
            $this->modx,
            $registry,
            $pipeline,
            new Psr7RequestFactory($this->modx),
            new ResponseEmitter(),
            $extensionApi,
            $apiPrefix,
            $webhookDispatcher,
            $authorizer,
        );

        return $this->application;
    }
}
