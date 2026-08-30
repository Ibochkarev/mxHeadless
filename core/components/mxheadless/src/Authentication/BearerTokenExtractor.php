<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

use Psr\Http\Message\ServerRequestInterface;

final class BearerTokenExtractor
{
    /**
     * True when the client sent Authorization: Bearer … or a non-empty X-API-Key.
     */
    public static function hasCredentialHeader(ServerRequestInterface $request): bool
    {
        if (trim($request->getHeaderLine('X-API-Key')) !== '') {
            return true;
        }

        $authorization = trim($request->getHeaderLine('Authorization'));

        return $authorization !== '' && preg_match('/^Bearer\s+\S+/i', $authorization) === 1;
    }

    public static function extract(ServerRequestInterface $request, string $prefix): ?string
    {
        $header = trim($request->getHeaderLine('X-API-Key'));
        if ($header !== '' && str_starts_with($header, $prefix)) {
            return $header;
        }

        $authorization = trim($request->getHeaderLine('Authorization'));
        if ($authorization === '' || preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches) !== 1) {
            return null;
        }

        $token = $matches[1];

        return str_starts_with($token, $prefix) ? $token : null;
    }
}
