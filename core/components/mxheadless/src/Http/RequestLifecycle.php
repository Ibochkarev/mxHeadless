<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestLifecycle
{
    public static function before(modX $modx, ServerRequestInterface $request): ServerRequestInterface
    {
        $modx->invokeEvent('OnMxHeadlessBeforeRequest', [
            'request' => $request,
            'identity' => $request->getAttribute('identity'),
            'route' => $request->getAttribute('route'),
        ]);

        return self::extractRequest($modx, $request);
    }

    public static function after(
        modX $modx,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $modx->invokeEvent('OnMxHeadlessAfterRequest', [
            'request' => $request,
            'response' => $response,
            'identity' => $request->getAttribute('identity'),
            'route' => $request->getAttribute('route'),
        ]);

        return self::extractResponse($modx, $response);
    }

    private static function extractRequest(modX $modx, ServerRequestInterface $fallback): ServerRequestInterface
    {
        $returned = self::returned($modx);
        if ($returned === null) {
            return $fallback;
        }

        $request = $returned['request'] ?? null;

        return $request instanceof ServerRequestInterface ? $request : $fallback;
    }

    private static function extractResponse(modX $modx, ResponseInterface $fallback): ResponseInterface
    {
        $returned = self::returned($modx);
        if ($returned === null) {
            return $fallback;
        }

        $response = $returned['response'] ?? null;

        return $response instanceof ResponseInterface ? $response : $fallback;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function returned(modX $modx): ?array
    {
        if (!isset($modx->Event) || !is_object($modx->Event)) {
            return null;
        }

        $returned = $modx->Event->returned ?? null;

        return is_array($returned) ? $returned : null;
    }
}
