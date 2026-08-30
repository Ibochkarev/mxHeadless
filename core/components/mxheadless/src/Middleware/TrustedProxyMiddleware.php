<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TrustedProxyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $trusted = $this->parseTrustedProxies();
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($trusted !== [] && in_array($remoteAddr, $trusted, true)) {
            $forwarded = $request->getHeaderLine('X-Forwarded-For');
            if ($forwarded !== '') {
                $parts = array_map('trim', explode(',', $forwarded));
                $clientIp = $parts[0] ?? $remoteAddr;
                $request = $request->withAttribute('client_ip', $clientIp);
            } else {
                $request = $request->withAttribute('client_ip', $remoteAddr);
            }
        } else {
            $request = $request->withAttribute('client_ip', $remoteAddr);
        }

        return $handler->handle($request);
    }

    /**
     * @return list<string>
     */
    private function parseTrustedProxies(): array
    {
        $raw = (string) $this->modx->getOption('mxheadless.trusted_proxies', null, '');

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
