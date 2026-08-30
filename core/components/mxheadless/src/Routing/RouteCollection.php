<?php

declare(strict_types=1);

namespace MxHeadless\Routing;

final class RouteCollection
{
    /** @var list<Route> */
    private array $routes = [];

    private bool $frozen = false;

    public function add(Route $route): void
    {
        if ($this->frozen) {
            throw new \RuntimeException('Route collection is frozen');
        }

        $this->routes[] = $route;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    /**
     * @return list<Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * @return list<array{
     *     name: string,
     *     methods: list<string>,
     *     pattern: string,
     *     public: bool,
     *     permission: string|null,
     *     object: string|null
     * }>
     */
    public function catalog(): array
    {
        return array_map(
            static fn (Route $route): array => $route->toCatalogEntry(),
            $this->routes,
        );
    }
}
