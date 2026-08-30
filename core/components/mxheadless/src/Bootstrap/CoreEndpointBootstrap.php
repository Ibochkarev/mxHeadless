<?php

declare(strict_types=1);

namespace MxHeadless\Bootstrap;

use MODX\Revolution\modX;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Extension\ExtensionApi;
use MxHeadless\Services\ContextSettingsService;
use Psr\Http\Message\ServerRequestInterface;

final class CoreEndpointBootstrap
{
    public static function register(ExtensionApi $api, modX $modx, Authorizer $authorizer): void
    {
        $service = new ContextSettingsService($modx, $authorizer);

        $api->registerEndpoint(
            'contexts.settings',
            ['GET'],
            '/contexts/{key}/settings',
            static function (ServerRequestInterface $request, array $params) use ($service): array {
                return $service->get($request, (string) $params['key']);
            },
            'contexts.read',
        );
    }
}
