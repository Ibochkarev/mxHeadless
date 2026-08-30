<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MODX\Revolution\modX;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Version;

final class DiscoveryService
{
    public function __construct(
        private readonly ApiPrefix $apiPrefix,
        private readonly modX $modx,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $base = $this->apiPrefix->versioned();

        return [
            'data' => [
                'name' => 'mxHeadless',
                'version' => Version::STRING,
                'api' => $base,
                'cors' => [
                    'enabled' => (bool) $this->modx->getOption('mxheadless_cors_enabled', null, false),
                    'allowed_origins' => $this->corsAllowedOrigins(),
                ],
                'links' => [
                    'health' => $base . '/health',
                    'schema' => $base . '/schema',
                    'docs' => $base . '/docs',
                    'endpoints' => $base . '/meta/endpoints',
                    'openapi' => $base . '/meta/openapi',
                    'openapi_json' => $base . '/meta/openapi.json',
                    'auth_token' => $base . '/auth/token',
                    'resources' => $base . '/resources',
                    'pages' => $base . '/pages/{uri}',
                    'contexts' => $base . '/contexts',
                    'chunks' => $base . '/chunks',
                    'templates' => $base . '/templates',
                    'snippets' => $base . '/snippets',
                    'tvs' => $base . '/tvs',
                    'categories' => $base . '/categories',
                    'content_types' => $base . '/content_types',
                    'objects' => $base . '/objects/{name}',
                ],
            ],
            'meta' => [],
        ];
    }

    /**
     * @return list<string>
     */
    private function corsAllowedOrigins(): array
    {
        $raw = (string) $this->modx->getOption('mxheadless_cors_allowed_origins', null, '');
        $origins = [];
        foreach (explode(',', $raw) as $origin) {
            $origin = trim($origin);
            if ($origin !== '') {
                $origins[] = $origin;
            }
        }

        return array_values(array_unique($origins));
    }
}
