<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;
use Psr\Http\Message\ServerRequestInterface;

final class RequestContext
{
    public static function path(ServerRequestInterface $request): string
    {
        $path = parse_url((string) $request->getUri(), PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    public static function contextKey(ServerRequestInterface $request, modX $modx): string
    {
        $header = trim($request->getHeaderLine('X-Context'));
        if ($header !== '') {
            return $header;
        }

        $query = $request->getQueryParams()['context'] ?? null;
        if (is_string($query) && $query !== '') {
            return $query;
        }

        return $modx->context ? (string) $modx->context->get('key') : 'web';
    }
}
