<?php

declare(strict_types=1);

namespace MxHeadless;

use MODX\Revolution\modX;
use MxHeadless\Bootstrap\CoreEndpointBootstrap;
use MxHeadless\Bootstrap\CoreObjectBootstrap;
use MxHeadless\Extension\ExtensionApi;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Http\MiddlewarePipeline;
use MxHeadless\Http\Psr7RequestFactory;
use MxHeadless\Http\ResponseEmitter;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Webhook\WebhookDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Application
{
    private bool $bootstrapped = false;

    public function __construct(
        private readonly modX $modx,
        private readonly ObjectRegistry $registry,
        private readonly MiddlewarePipeline $pipeline,
        private readonly Psr7RequestFactory $requestFactory,
        private readonly ResponseEmitter $emitter,
        private readonly ExtensionApi $extensionApi,
        private readonly ApiPrefix $apiPrefix,
        private readonly WebhookDispatcher $webhookDispatcher,
        private readonly Authorizer $authorizer,
    ) {
    }

    public function extensionApi(): ExtensionApi
    {
        return $this->extensionApi;
    }

    public function registry(): ObjectRegistry
    {
        return $this->registry;
    }

    public function webhookDispatcher(): WebhookDispatcher
    {
        return $this->webhookDispatcher;
    }

    public function apiPrefix(): ApiPrefix
    {
        return $this->apiPrefix;
    }

    public function handleFromGlobals(): void
    {
        $request = $this->requestFactory->createFromGlobals();
        $response = $this->handle($request);
        $this->finalizePhpSession();
        $this->emitter->emit($response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->bootstrap();

        return $this->pipeline->handle($request);
    }

    /**
     * Close the PHP session (release lock) and drop Set-Cookie on API responses.
     * Incoming Cookie headers still authenticate session users; Nuxt/Next ISR must not see PHPSESSID.
     */
    private function finalizePhpSession(): void
    {
        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (!headers_sent()) {
            header_remove('Set-Cookie');
        }
    }

    public function bootstrap(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        CoreObjectBootstrap::register($this->extensionApi);
        CoreEndpointBootstrap::register($this->extensionApi, $this->modx, $this->authorizer);
        $this->modx->invokeEvent('OnMxHeadlessRegister', [
            'api' => $this->extensionApi,
        ]);
        $this->extensionApi->freeze();
        $this->bootstrapped = true;
    }

    public function matchesPath(string $path): bool
    {
        return $this->apiPrefix->matchesPath($path);
    }
}
