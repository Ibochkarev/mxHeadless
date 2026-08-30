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
            return $this->stripModxFriendlyUrlQuery($_GET);
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

        return $this->stripModxFriendlyUrlQuery($queryParams);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function stripModxFriendlyUrlQuery(array $params): array
    {
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
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($path, '?') ?: '/';

        $query = $this->resolveQueryParams();
        $uri = $scheme . '://' . $host . $path;
        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
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
