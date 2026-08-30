<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MxHeadless\Http\ApiPrefix;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Version;

final class OpenApiGenerator
{
    public function __construct(
        private readonly RouteCollection $routes,
        private readonly ObjectRegistry $registry,
        private readonly ApiPrefix $apiPrefix,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $paths = [];
        foreach ($this->routes->all() as $route) {
            $pathKey = $this->openApiPath($route->pattern());
            if (!isset($paths[$pathKey])) {
                $paths[$pathKey] = [];
            }

            foreach ($route->methods() as $method) {
                $summary = $route->description() ?? $route->name();
                $operation = [
                    'operationId' => $route->name() . '.' . strtolower($method),
                    'summary' => $summary,
                    'tags' => $this->tagsForRoute($route),
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Envelope'],
                                ],
                            ],
                        ],
                        'default' => [
                            'description' => 'Error',
                            'content' => [
                                'application/problem+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/ProblemDetails'],
                                ],
                            ],
                        ],
                    ],
                ];

                if (!$route->isPublic()) {
                    $operation['security'] = [['bearerAuth' => []]];
                } else {
                    $operation['security'] = [];
                }

                if ($route->description() !== null && $route->description() !== $summary) {
                    $operation['description'] = $route->description();
                }

                $params = $this->parametersForRoute($route);
                if ($params !== []) {
                    $operation['parameters'] = $params;
                }

                $paths[$pathKey][strtolower($method)] = $operation;
            }
        }

        ksort($paths);

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'mxHeadless API',
                'version' => Version::STRING,
                'description' => 'Live OpenAPI generated from RouteCollection and ObjectRegistry.',
            ],
            'servers' => [
                ['url' => $this->apiPrefix->versioned()],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'mxh API key',
                    ],
                ],
                'schemas' => [
                    'Envelope' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['nullable' => true],
                            'meta' => ['type' => 'object'],
                            'links' => ['type' => 'object'],
                        ],
                    ],
                    'ProblemDetails' => [
                        'type' => 'object',
                        'required' => ['type', 'title', 'status'],
                        'properties' => [
                            'type' => ['type' => 'string', 'format' => 'uri'],
                            'title' => ['type' => 'string'],
                            'status' => ['type' => 'integer'],
                            'detail' => ['type' => 'string'],
                            'instance' => ['type' => 'string'],
                            'code' => [
                                'type' => 'string',
                                'description' => 'Stable machine-readable error code',
                                'enum' => [
                                    'service_disabled',
                                    'token_required',
                                    'invalid_token',
                                    'scope_denied',
                                    'rate_limited',
                                    'idempotency_conflict',
                                    'invalid_grant',
                                    'not_found',
                                    'validation_failed',
                                    'internal_error',
                                ],
                            ],
                            'errors' => ['type' => 'object'],
                        ],
                    ],
                    'RegisteredObjects' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'type' => 'object',
                            'properties' => [
                                'class' => ['type' => 'string'],
                                'fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'readable' => ['type' => 'boolean'],
                                'creatable' => ['type' => 'boolean'],
                                'updatable' => ['type' => 'boolean'],
                                'deletable' => ['type' => 'boolean'],
                            ],
                        ],
                        'example' => array_keys($this->registry->all()),
                    ],
                ],
            ],
            'x-mxheadless' => [
                'objects' => array_keys($this->registry->all()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'data' => $this->generate(),
            'meta' => [
                'routes' => count($this->routes->all()),
                'objects' => count($this->registry->all()),
            ],
        ];
    }

    private function openApiPath(string $pattern): string
    {
        return preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '{$1}', $pattern) ?? $pattern;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parametersForRoute(Route $route): array
    {
        $params = [];
        foreach ($route->parameters() as $parameter) {
            $params[$parameter->in() . ':' . $parameter->name()] = $parameter->toOpenApi();
        }

        foreach ($this->pathParameters($route->pattern()) as $pathParam) {
            $key = 'path:' . $pathParam['name'];
            if (!isset($params[$key])) {
                $params[$key] = $pathParam;
            }
        }

        return array_values($params);
    }

    /**
     * @return list<string>
     */
    private function tagsForRoute(Route $route): array
    {
        if ($route->tags() !== []) {
            return $route->tags();
        }

        return [$this->tagForRoute($route->name())];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pathParameters(string $pattern): array
    {
        if (!preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', $pattern, $matches)) {
            return [];
        }

        $params = [];
        foreach ($matches[1] as $name) {
            $params[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }

        return $params;
    }

    private function tagForRoute(string $name): string
    {
        $parts = explode('.', $name);

        return $parts[0] !== '' ? ucfirst($parts[0]) : 'Default';
    }
}
