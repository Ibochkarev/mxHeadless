<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Authentication\Identity;
use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Http\Psr7Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpCacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
        private readonly ModxCacheAdapter $cache,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $handler->handle($request);
        }

        // HEAD must share GET representation so ETag matches Nuxt/Next freshness checks.
        $effective = $method === 'HEAD' ? $request->withMethod('GET') : $request;

        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        $isPrivate = $identity !== null && !$identity->isAnonymous();
        $sharedCache = !$isPrivate && (bool) $this->modx->getOption('mxheadless_cache_enabled', null, true);

        if (!$sharedCache) {
            return $this->attachEtag(
                $handler->handle($effective),
                $request,
                $method,
                true,
            );
        }

        $cacheKey = $this->buildCacheKey($request);
        $cached = $this->cache->get($cacheKey, 'response');
        if (is_array($cached) && isset($cached['body'], $cached['etag'])) {
            $contentType = is_string($cached['content_type'] ?? null)
                ? $cached['content_type']
                : 'application/json; charset=utf-8';
            $ifNoneMatch = $request->getHeaderLine('If-None-Match');
            if ($ifNoneMatch !== '' && trim($ifNoneMatch, '"') === $cached['etag']) {
                return $this->cachedResponse(304, $cached['etag'], null, false, $contentType);
            }

            return $this->cachedResponse(
                200,
                $cached['etag'],
                $method === 'HEAD' ? null : $cached['body'],
                false,
                $contentType,
            );
        }

        $response = $handler->handle($effective);
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return $this->maybeHead($response, $method);
        }

        $body = (string) $response->getBody();
        $etag = hash('sha256', $body);
        $ttl = $this->cacheTtl();
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType === '') {
            $contentType = 'application/json; charset=utf-8';
        }
        $this->cache->set($cacheKey, [
            'body' => $body,
            'etag' => $etag,
            'content_type' => $contentType,
        ], $ttl, 'response');

        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch !== '' && trim($ifNoneMatch, '"') === $etag) {
            return $this->cachedResponse(304, $etag, null, false, $contentType);
        }

        return $this->maybeHead(
            $response
                ->withBody(Psr7Factory::create()->createStream($body))
                ->withHeader('ETag', '"' . $etag . '"')
                ->withHeader('Cache-Control', 'public, max-age=' . $ttl),
            $method,
        );
    }

    private function attachEtag(
        ResponseInterface $response,
        ServerRequestInterface $request,
        string $method,
        bool $private,
    ): ResponseInterface {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $response = $private
                ? $response->withHeader('Cache-Control', 'private, no-store')
                : $response;

            return $this->maybeHead($response, $method);
        }

        $body = (string) $response->getBody();
        $etag = hash('sha256', $body);
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch !== '' && trim($ifNoneMatch, '"') === $etag) {
            $contentType = $response->getHeaderLine('Content-Type');
            if ($contentType === '') {
                $contentType = 'application/json; charset=utf-8';
            }

            return $this->cachedResponse(304, $etag, null, $private, $contentType);
        }

        $cacheControl = $private
            ? 'private, no-store'
            : 'public, max-age=' . $this->cacheTtl();

        return $this->maybeHead(
            $response
                ->withBody(Psr7Factory::create()->createStream($body))
                ->withHeader('ETag', '"' . $etag . '"')
                ->withHeader('Cache-Control', $cacheControl),
            $method,
        );
    }

    private function maybeHead(ResponseInterface $response, string $method): ResponseInterface
    {
        if ($method !== 'HEAD') {
            return $response;
        }

        return $response->withBody(Psr7Factory::create()->createStream(''));
    }

    private function cacheTtl(): int
    {
        return (int) $this->modx->getOption('mxheadless_cache_ttl', null, 300);
    }

    private function cachedResponse(
        int $status,
        string $etag,
        ?string $body = null,
        bool $private = false,
        string $contentType = 'application/json; charset=utf-8',
    ): ResponseInterface {
        $headers = [
            'ETag' => '"' . $etag . '"',
            'Cache-Control' => $private
                ? 'private, no-store'
                : 'public, max-age=' . $this->cacheTtl(),
            'Content-Type' => $contentType,
        ];

        return Psr7Factory::createResponse($status, $headers, $body);
    }

    private function buildCacheKey(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getScheme() . '://' . $uri->getAuthority() . $uri->getPath();
        $query = $request->getQueryParams();
        ksort($query);
        $queryString = http_build_query($query);
        $context = $request->getHeaderLine('X-Context') ?: ($query['context'] ?? 'web');
        $objectTag = (string) ($request->getAttribute('cache_object') ?? 'global');
        $versions = $this->cache->getTagVersion('object:' . $objectTag)
            . ':' . $this->cache->getTagVersion('context:' . (string) $context);

        return hash('sha256', $path . '?' . $queryString . '|' . $context . '|' . $versions);
    }
}
