<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BodyLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $maxBody = (int) $this->modx->getOption('mxheadless_max_body_bytes', null, 1048576);
        $maxUri = (int) $this->modx->getOption('mxheadless_max_uri_bytes', null, 2048);

        $path = parse_url((string) $request->getUri(), PHP_URL_PATH) ?: '';
        if (strlen($path) > $maxUri) {
            throw new HttpException('URI too long', 414, 'URI Too Long');
        }

        $contentLength = (int) ($request->getHeaderLine('Content-Length') ?: 0);
        if ($contentLength > $maxBody) {
            throw new HttpException('Request body too large', 413, 'Payload Too Large');
        }

        return $handler->handle($request);
    }
}
