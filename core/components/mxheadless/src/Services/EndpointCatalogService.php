<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MxHeadless\Routing\RouteCollection;

final class EndpointCatalogService
{
    public function __construct(
        private readonly RouteCollection $routes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $endpoints = $this->routes->catalog();

        return [
            'data' => [
                'endpoints' => $endpoints,
            ],
            'meta' => [
                'count' => count($endpoints),
            ],
        ];
    }
}
