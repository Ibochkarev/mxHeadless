<?php

declare(strict_types=1);

namespace MxHeadless\Extension;

use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Definition\RelationDefinition;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\Route;
use MxHeadless\Routing\RouteCollection;
use MxHeadless\Routing\RouteParameter;
use Psr\Http\Message\ServerRequestInterface;

final class ExtensionApi
{
    public function __construct(
        private readonly ObjectRegistry $registry,
        private readonly RouteCollection $routes,
    ) {
    }

    public function registerObject(ObjectDefinition $definition): void
    {
        $this->registry->register($definition);
    }

    public function registerRelation(string $objectName, RelationDefinition $relation): void
    {
        $definition = $this->registry->get($objectName);
        if ($definition === null) {
            throw new \InvalidArgumentException('Unknown object: ' . $objectName);
        }

        $this->registry->register($definition->relation($relation));
    }

    /**
     * @param list<string> $methods
     * @param callable(ServerRequestInterface, array<string, string>): array<string, mixed> $handler
     * @param array<string, string> $defaults
     * @param list<string> $tags
     * @param list<RouteParameter> $parameters
     */
    public function registerEndpoint(
        string $name,
        array $methods,
        string $pattern,
        callable $handler,
        ?string $permission = null,
        bool $public = false,
        array $defaults = [],
        ?string $description = null,
        array $tags = [],
        array $parameters = [],
    ): void {
        $this->routes->add(new Route(
            $name,
            $methods,
            $pattern,
            $handler,
            $permission,
            $public,
            $defaults,
            null,
            null,
            $description,
            $tags,
            $parameters,
        ));
    }

    public function freeze(): void
    {
        $this->registry->freeze();
        $this->routes->freeze();
    }

    public function registry(): ObjectRegistry
    {
        return $this->registry;
    }
}
