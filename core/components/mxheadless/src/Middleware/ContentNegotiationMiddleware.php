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
        if ($accept !== '' && !str_contains($accept, 'application/json') && !str_contains($accept, '*/*')) {
            throw new HttpException('Not acceptable', 406, 'Not Acceptable');
        }

        return $handler->handle($request);
    }
}
