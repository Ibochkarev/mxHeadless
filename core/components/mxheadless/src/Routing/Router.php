<?php

declare(strict_types=1);

namespace MxHeadless\Routing;

use MxHeadless\Http\ApiPrefix;
use Psr\Http\Message\ServerRequestInterface;

final class Router
{
    public function __construct(
        private readonly RouteCollection $routes,
        private readonly ApiPrefix $apiPrefix,
    ) {
    }

    /**
     * @return array{route: Route, params: array<string, string>}|null
     */
    public function match(ServerRequestInterface $request): ?array
    {
        $method = strtoupper($request->getMethod());
        $path = parse_url((string) $request->getUri(), PHP_URL_PATH) ?: '/';
        $relative = $this->apiPrefix->stripVersionedPrefix($path);
        if ($relative === $path) {
            return null;
        }

        foreach ($this->routes->all() as $route) {
            if (!in_array($method, $route->methods(), true) && !($method === 'HEAD' && in_array('GET', $route->methods(), true))) {
                continue;
            }

            $params = $this->matchPattern($route->pattern(), $relative);
            if ($params !== null) {
                return ['route' => $route, 'params' => array_merge($route->defaults(), $params)];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>|null
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        if (str_ends_with($pattern, '{uri}')) {
            $prefix = substr($pattern, 0, -5);
            if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) {
                return ['uri' => ltrim(substr($path, strlen(rtrim($prefix, '/'))), '/')];
            }

            return null;
        }

        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}(?:/|$)#', '(?<$1>[^/]+)/', rtrim($pattern, '/') . '/') . '$#';
        if (!preg_match($regex, rtrim($path, '/') . '/', $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = rawurldecode($value);
            }
        }

        return $params;
    }
}
