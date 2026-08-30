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
                    'enabled' => (bool) $this->modx->getOption('mxheadless.cors.enabled', null, false),
                    'allowed_origins' => $this->corsAllowedOrigins(),
                ],
                'links' => [
                    'health' => $base . '/health',
                    'schema' => $base . '/schema',
                    'endpoints' => $base . '/meta/endpoints',
                    'openapi' => $base . '/meta/openapi',
                    'pages' => $base . '/pages/{uri}',
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
        $raw = (string) $this->modx->getOption('mxheadless.cors.allowed_origins', null, '');
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
