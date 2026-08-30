<?php

declare(strict_types=1);

namespace MxHeadless\Routing;

use Psr\Http\Message\ServerRequestInterface;

final class Route
{
    /**
     * @param list<string> $methods
     * @param array<string, string> $defaults
     * @param callable(ServerRequestInterface, array<string, string>): array<string, mixed> $handler
     * @param callable(array<string, string>): string|null $permissionResolver
     * @param list<RouteParameter> $parameters
     */
    public function __construct(
        private readonly string $name,
        private readonly array $methods,
        private readonly string $pattern,
        private readonly mixed $handler,
        private readonly ?string $permission = null,
        private readonly bool $public = false,
        private readonly array $defaults = [],
        private readonly mixed $permissionResolver = null,
        private readonly ?string $objectName = null,
        private readonly ?string $description = null,
        private readonly array $tags = [],
        private readonly array $parameters = [],
    ) {
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException('Route handler must be callable');
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return callable(ServerRequestInterface, array<string, string>): array<string, mixed>
     */
    public function handler(): callable
    {
        return $this->handler;
    }

    public function permission(): ?string
    {
        return $this->permission;
    }

    /**
     * @param array<string, string> $params
     */
    public function resolvePermission(array $params): ?string
    {
        if ($this->permission !== null) {
            return $this->permission;
        }

        if ($this->permissionResolver !== null) {
            return ($this->permissionResolver)($params);
        }

        return null;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function objectName(): ?string
    {
        return $this->objectName;
    }

    /**
     * @param array<string, string> $params
     */
    public function cacheTag(array $params): string
    {
        if ($this->objectName !== null) {
            return $this->objectName;
        }

        if (isset($params['name'])) {
            return (string) $params['name'];
        }

        return 'global';
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return $this->defaults;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    /**
     * @return list<RouteParameter>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array{
     *     name: string,
     *     methods: list<string>,
     *     pattern: string,
     *     public: bool,
     *     permission: string|null,
     *     object: string|null,
     *     description: string|null,
     *     tags: list<string>,
     *     parameters: list<array<string, mixed>>
     * }
     */
    public function toCatalogEntry(): array
    {
        return [
            'name' => $this->name,
            'methods' => $this->methods,
            'pattern' => $this->pattern,
            'public' => $this->public,
            'permission' => $this->permission,
            'object' => $this->objectName,
            'description' => $this->description,
            'tags' => $this->tags,
            'parameters' => array_map(
                static fn (RouteParameter $parameter): array => $parameter->toCatalogEntry(),
                $this->parameters,
            ),
        ];
    }
}
