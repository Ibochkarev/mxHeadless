<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MxHeadless\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ContentNegotiationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = $request->getHeaderLine('Content-Type');
            if (
                $contentType !== ''
                && !str_contains($contentType, 'application/json')
                && !str_contains($contentType, 'application/x-www-form-urlencoded')
            ) {
                throw new HttpException('Unsupported media type', 415, 'Unsupported Media Type');
            }
        }

        $accept = $request->getHeaderLine('Accept');
        if ($accept !== '' && !$this->acceptsJsonOrWildcard($accept) && !$this->allowsHtmlDocs($request, $accept)) {
            throw new HttpException('Not acceptable', 406, 'Not Acceptable');
        }

        return $handler->handle($request);
    }

    private function acceptsJsonOrWildcard(string $accept): bool
    {
        return str_contains($accept, 'application/json')
            || str_contains($accept, 'application/openapi+json')
            || str_contains($accept, '*/*');
    }

    private function allowsHtmlDocs(ServerRequestInterface $request, string $accept): bool
    {
        if (!str_contains($accept, 'text/html')) {
            return false;
        }

        $path = parse_url((string) $request->getUri(), PHP_URL_PATH) ?: '';

        return str_ends_with(rtrim($path, '/'), '/docs');
    }
}
