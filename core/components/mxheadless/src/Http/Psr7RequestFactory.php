<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr7RequestFactory
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function createFromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->buildUri();
        $headers = $this->parseHeaders();

        $requestFactory = Psr7ServiceResolver::resolve($this->modx, ServerRequestFactoryInterface::class);
        $streamFactory = Psr7ServiceResolver::resolve($this->modx, StreamFactoryInterface::class);

        $request = $requestFactory->createServerRequest($method, $uri, $_SERVER);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $body = file_get_contents('php://input') ?: '';
        if ($body !== '') {
            $request = $request->withBody($streamFactory->createStream($body));
        }

        return $request
            ->withQueryParams($this->resolveQueryParams())
            ->withParsedBody($_POST ?: null);
    }

    /**
     * Prefer PHP's already-decoded $_GET. Some stacks (MODX/nginx) leave
     * QUERY_STRING HTML-entity-encoded (`&amp;`), which breaks parse_str().
     * Also strip MODX friendly-URL `q` (request path), which collides with API search.
     *
     * @return array<string, mixed>
     */
    private function resolveQueryParams(): array
    {
        if ($_GET !== []) {
            return $this->stripRoutingQueryParams($_GET);
        }

        $queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($queryString === '') {
            return [];
        }

        if (str_contains($queryString, '&amp;')) {
            $queryString = html_entity_decode($queryString, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        }

        $queryParams = [];
        parse_str($queryString, $queryParams);

        return $this->stripRoutingQueryParams($queryParams);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function stripRoutingQueryParams(array $params): array
    {
        if ($this->isFallbackEntrypoint()) {
            unset($params['route'], $params['path']);
        }

        if (!isset($params['q']) || !is_string($params['q'])) {
            return $params;
        }

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return $params;
        }

        $normalized = ltrim($path, '/');
        if ($params['q'] === $normalized || $params['q'] === $path) {
            unset($params['q']);
        }

        return $params;
    }

    private function buildUri(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $this->resolveRequestPath();

        $query = $this->resolveQueryParams();
        $uri = $scheme . '://' . $host . $path;
        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    /**
     * Map assets/.../mxheadless/api.php onto the configured /api/v1 tree.
     *
     * Supports PATH_INFO (`api.php/v1/health`) when the web server provides it, and
     * nginx-friendly `?route=/v1/health` (or `?route=/api/v1/health`) when it does not.
     */
    private function resolveRequestPath(): string
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url(strtok($requestUri, '?') ?: '/', PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        if (!$this->isFallbackEntrypoint()) {
            return $path;
        }

        $relative = $this->fallbackRelativePath($path);
        $base = rtrim((string) $this->modx->getOption('mxheadless.api.prefix', null, '/api'), '/');
        if ($base === '') {
            $base = '/api';
        }

        if ($relative === '' || $relative === '/') {
            return $base . '/v1';
        }

        if (str_starts_with($relative, $base . '/') || $relative === $base) {
            return $relative;
        }

        if (str_starts_with($relative, '/v1/') || $relative === '/v1') {
            return $base . $relative;
        }

        return $base . '/v1' . (str_starts_with($relative, '/') ? $relative : '/' . $relative);
    }

    private function isFallbackEntrypoint(): bool
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return str_ends_with($scriptName, '/mxheadless/api.php');
    }

    private function fallbackRelativePath(string $requestPath): string
    {
        $pathInfo = (string) ($_SERVER['PATH_INFO'] ?? '');
        if ($pathInfo === '' && isset($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = (string) $_SERVER['ORIG_PATH_INFO'];
        }
        if ($pathInfo !== '' && $pathInfo !== '/') {
            return $pathInfo;
        }

        if (preg_match('#/api\\.php(/.*)$#', $requestPath, $matches) === 1) {
            return $matches[1];
        }

        $route = $_GET['route'] ?? $_GET['path'] ?? null;
        if (is_string($route) && $route !== '') {
            $route = strtok($route, '?') ?: $route;

            return str_starts_with($route, '/') ? $route : '/' . $route;
        }

        return '/v1';
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }
}
