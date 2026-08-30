<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MxHeadless\Http\ApiPrefix;
use MxHeadless\Http\Psr7Factory;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Version;
use Psr\Http\Message\ResponseInterface;

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
                $methodLower = strtolower($method);
                $successStatus = str_ends_with($route->name(), '.create') && $methodLower === 'post'
                    ? '201'
                    : '200';
                $operation = [
                    'operationId' => $route->name() . '.' . $methodLower,
                    'summary' => $summary,
                    'tags' => $this->tagsForRoute($route),
                    'responses' => [
                        $successStatus => [
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
                    // Optional bearer: {} = anonymous OK; second entry enables Swagger Authorize.
                    $operation['security'] = [
                        new \stdClass(),
                        ['bearerAuth' => []],
                    ];
                }

                if ($route->description() !== null && $route->description() !== $summary) {
                    $operation['description'] = $route->description();
                }

                $params = $this->parametersForRoute($route);
                if ($params !== []) {
                    $operation['parameters'] = $params;
                }

                $requestBody = $this->requestBodyForMethod($methodLower, $route->name());
                if ($requestBody !== null) {
                    $operation['requestBody'] = $requestBody;
                }

                $paths[$pathKey][$methodLower] = $operation;
            }
        }

        ksort($paths);

        $tagNames = [];
        foreach ($paths as $operations) {
            foreach ($operations as $operation) {
                if (!is_array($operation)) {
                    continue;
                }
                foreach ($operation['tags'] as $tag) {
                    if (is_string($tag) && $tag !== '') {
                        $tagNames[$tag] = true;
                    }
                }
            }
        }
        ksort($tagNames);
        $tags = [];
        foreach (array_keys($tagNames) as $tagName) {
            $tags[] = ['name' => $tagName];
        }

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
            'tags' => $tags,
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
                    'MutationBody' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'description' => 'Writable object fields (see ObjectDefinition /schema)',
                        'example' => [
                            'pagetitle' => 'Draft',
                            'published' => 0,
                        ],
                    ],
                    'OAuthTokenRequest' => [
                        'type' => 'object',
                        'required' => ['grant_type', 'client_id', 'client_secret'],
                        'properties' => [
                            'grant_type' => [
                                'type' => 'string',
                                'enum' => ['client_credentials', 'password'],
                            ],
                            'client_id' => ['type' => 'string'],
                            'client_secret' => ['type' => 'string'],
                            'username' => ['type' => 'string'],
                            'password' => ['type' => 'string'],
                            'scope' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'x-mxheadless' => [
                'objects' => array_keys($this->registry->all()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestBodyForMethod(string $methodLower, string $routeName): ?array
    {
        if (!in_array($methodLower, ['post', 'put', 'patch'], true)) {
            return null;
        }

        if ($routeName === 'auth.token') {
            return [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/OAuthTokenRequest'],
                    ],
                    'application/x-www-form-urlencoded' => [
                        'schema' => ['$ref' => '#/components/schemas/OAuthTokenRequest'],
                    ],
                ],
            ];
        }

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/MutationBody'],
                ],
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

    public function handleRaw(): ResponseInterface
    {
        return Psr7Factory::json($this->generate(), 200, [
            'Content-Type' => 'application/openapi+json; charset=utf-8',
            'Cache-Control' => 'public, max-age=60',
        ]);
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

        $name = $route->name();
        if (str_ends_with($name, '.list')) {
            foreach ($this->collectionQueryParameters($route) as $queryParam) {
                $key = $queryParam['in'] . ':' . $queryParam['name'];
                if (!isset($params[$key])) {
                    $params[$key] = $queryParam;
                }
            }
        } elseif (str_ends_with($name, '.get') || $name === 'pages.get') {
            foreach ($this->itemQueryParameters($route) as $queryParam) {
                $key = $queryParam['in'] . ':' . $queryParam['name'];
                if (!isset($params[$key])) {
                    $params[$key] = $queryParam;
                }
            }
        } elseif (str_ends_with($name, '.delete')) {
            foreach ($this->deleteQueryParameters($route) as $queryParam) {
                $key = $queryParam['in'] . ':' . $queryParam['name'];
                if (!isset($params[$key])) {
                    $params[$key] = $queryParam;
                }
            }
        }

        return array_values($params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectionQueryParameters(Route $route): array
    {
        $definition = null;
        $objectName = $route->objectName();
        if ($objectName !== null) {
            $definition = $this->registry->get($objectName);
        }

        $sortExample = 'id';
        $includeExample = null;
        $hasRelations = $definition === null;
        $supportsSoftDelete = false;
        $supportsPreview = false;
        $filterFields = ['id'];

        if ($definition !== null) {
            $sortable = $definition->getSortable();
            if ($sortable !== []) {
                $sortExample = $sortable[0];
                if (isset($sortable[1])) {
                    $sortExample = '-' . $sortable[0] . ',' . $sortable[1];
                }
            }

            $relations = $definition->relations();
            $hasRelations = $relations !== [];
            if ($hasRelations) {
                $includeExample = $relations[0]->name();
            }

            $filterable = $definition->getFilterable();
            if ($filterable !== []) {
                $filterFields = array_slice($filterable, 0, 2);
            }

            $supportsSoftDelete = in_array('deleted', $definition->getFields(), true)
                || in_array('deleted', $filterable, true);
            $supportsPreview = in_array('published', $definition->getFields(), true)
                || in_array('published', $filterable, true)
                || $supportsSoftDelete;
        }

        $params = [
            [
                'name' => 'limit',
                'in' => 'query',
                'required' => false,
                'description' => 'Page size (clamped to max_limit)',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20],
            ],
            [
                'name' => 'offset',
                'in' => 'query',
                'required' => false,
                'description' => 'Row offset; mutually exclusive with page',
                'schema' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
            ],
            [
                'name' => 'page',
                'in' => 'query',
                'required' => false,
                'description' => '1-based page; mutually exclusive with offset',
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            [
                'name' => 'fields',
                'in' => 'query',
                'required' => false,
                'description' => 'Comma-separated sparse fieldset',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'sort',
                'in' => 'query',
                'required' => false,
                'description' => 'Comma-separated fields; prefix - for DESC, + for ASC',
                'schema' => ['type' => 'string', 'example' => $sortExample],
            ],
        ];

        if ($hasRelations) {
            $params[] = [
                'name' => 'include',
                'in' => 'query',
                'required' => false,
                'description' => 'Comma-separated relations (dot notation for nested)',
                'schema' => $includeExample !== null
                    ? ['type' => 'string', 'example' => $includeExample]
                    : ['type' => 'string'],
            ];
        }

        $params[] = [
            'name' => 'q',
            'in' => 'query',
            'required' => false,
            'description' => 'Search term across searchable fields',
            'schema' => ['type' => 'string'],
        ];
        $params[] = [
            'name' => 'context',
            'in' => 'query',
            'required' => false,
            'schema' => ['type' => 'string', 'default' => 'web'],
        ];

        if ($definition === null || $supportsPreview) {
            $params[] = [
                'name' => 'preview',
                'in' => 'query',
                'required' => false,
                'description' => 'Include unpublished content when authorized',
                'schema' => ['type' => 'boolean', 'default' => false],
            ];
        }

        if ($definition === null || $supportsSoftDelete) {
            $params[] = [
                'name' => 'include_deleted',
                'in' => 'query',
                'required' => false,
                'description' => 'Include soft-deleted rows when authorized',
                'schema' => ['type' => 'boolean', 'default' => false],
            ];
        }

        foreach ($filterFields as $field) {
            $params[] = [
                'name' => 'filter[' . $field . '][eq]',
                'in' => 'query',
                'required' => false,
                'description' => 'Example filter; any filter[field][op] for filterable fields is accepted',
                'schema' => ['type' => 'string'],
            ];
        }

        return $params;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itemQueryParameters(Route $route): array
    {
        $definition = null;
        $objectName = $route->objectName();
        if ($objectName !== null) {
            $definition = $this->registry->get($objectName);
        }

        $hasRelations = $definition === null;
        $includeExample = null;
        $supportsPreview = true;
        $supportsSoftDelete = false;
        if ($definition !== null) {
            $relations = $definition->relations();
            $hasRelations = $relations !== [];
            if ($hasRelations) {
                $includeExample = $relations[0]->name();
            }

            $supportsSoftDelete = in_array('deleted', $definition->getFields(), true);
            $supportsPreview = in_array('published', $definition->getFields(), true)
                || in_array('published', $definition->getFilterable(), true)
                || $supportsSoftDelete;
        }

        $params = [
            [
                'name' => 'fields',
                'in' => 'query',
                'required' => false,
                'description' => 'Comma-separated sparse fieldset',
                'schema' => ['type' => 'string'],
            ],
        ];

        if ($hasRelations) {
            $params[] = [
                'name' => 'include',
                'in' => 'query',
                'required' => false,
                'description' => 'Comma-separated relations (dot notation for nested)',
                'schema' => $includeExample !== null
                    ? ['type' => 'string', 'example' => $includeExample]
                    : ['type' => 'string'],
            ];
        }

        $params[] = [
            'name' => 'context',
            'in' => 'query',
            'required' => false,
            'schema' => ['type' => 'string', 'default' => 'web'],
        ];

        if ($supportsPreview) {
            $params[] = [
                'name' => 'preview',
                'in' => 'query',
                'required' => false,
                'description' => 'Include unpublished content when authorized',
                'schema' => ['type' => 'boolean', 'default' => false],
            ];
        }

        if ($definition === null || $supportsSoftDelete) {
            $params[] = [
                'name' => 'include_deleted',
                'in' => 'query',
                'required' => false,
                'description' => 'Include soft-deleted rows when authorized',
                'schema' => ['type' => 'boolean', 'default' => false],
            ];
        }

        return $params;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function deleteQueryParameters(Route $route): array
    {
        $definition = null;
        $objectName = $route->objectName();
        if ($objectName !== null) {
            $definition = $this->registry->get($objectName);
        }

        $supportsSoftDelete = $definition === null
            || in_array('deleted', $definition->getFields(), true);

        if (!$supportsSoftDelete) {
            return [];
        }

        return [
            [
                'name' => 'force',
                'in' => 'query',
                'required' => false,
                'description' => 'Permanently remove the row (including soft-deleted). Default is soft-delete when supported.',
                'schema' => ['type' => 'boolean', 'default' => false],
            ],
        ];
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
            $param = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];

            if ($name === 'id') {
                $param['schema'] = ['type' => 'string', 'description' => 'Object primary key'];
            } elseif ($name === 'key') {
                $param['description'] = 'Context key (primary key)';
                $param['schema'] = ['type' => 'string', 'example' => 'web'];
            } elseif ($name === 'uri') {
                $param['description'] = 'Resource URI / alias path (may contain slashes)';
                $param['schema'] = ['type' => 'string', 'example' => 'index'];
            } elseif ($name === 'name') {
                $param['description'] = 'Registered object name';
                $param['schema'] = ['type' => 'string', 'pattern' => '^[a-z][a-z0-9_]*$'];
            }

            $params[] = $param;
        }

        return $params;
    }

    private function tagForRoute(string $name): string
    {
        $parts = explode('.', $name);
        $raw = $parts[0] !== '' ? $parts[0] : 'Default';

        return match ($raw) {
            'tvs' => 'TVs',
            default => str_replace(' ', '', ucwords(str_replace('_', ' ', $raw))),
        };
    }
}
