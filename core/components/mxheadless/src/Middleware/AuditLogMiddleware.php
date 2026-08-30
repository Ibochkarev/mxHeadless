<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Audit\AuditLogWriter;
use MxHeadless\Http\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuditLogMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly modX $modx,
        private readonly AuditLogWriter $auditLog,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!(bool) $this->modx->getOption('mxheadless.audit.enabled', null, false)) {
            return $handler->handle($request);
        }

        $method = strtoupper($request->getMethod());
        if (!$this->shouldLog($method)) {
            return $handler->handle($request);
        }

        $started = hrtime(true);
        $response = $handler->handle($request);
        $durationMs = (int) round((hrtime(true) - $started) / 1_000_000);

        try {
            $this->auditLog->append([
                'request_id' => (string) ($request->getAttribute('request_id') ?? ''),
                'identity_key' => (string) ($request->getAttribute('identity_key') ?? 'anonymous'),
                'api_key_id' => $this->apiKeyId($request),
                'method' => $method,
                'path' => RequestContext::path($request),
                'context_key' => RequestContext::contextKey($request, $this->modx),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => max(0, $durationMs),
            ]);
        } catch (\Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[mxHeadless] Audit log write failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function shouldLog(string $method): bool
    {
        if (in_array($method, self::MUTATING_METHODS, true)) {
            return true;
        }

        return $method === 'GET'
            && (bool) $this->modx->getOption('mxheadless.audit.log_get', null, false);
    }

    private function apiKeyId(ServerRequestInterface $request): ?int
    {
        $id = $request->getAttribute('api_key_id');
        if ($id === null) {
            return null;
        }

        $parsed = (int) $id;

        return $parsed > 0 ? $parsed : null;
    }
}
