<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Audit\AuditLogWriter;
use MxHeadless\Middleware\AuditLogMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuditLogMiddlewareTest extends TestCase
{
    public function testSkipsLoggingWhenDisabled(): void
    {
        $repository = new RecordingAuditLogWriter();
        $middleware = new AuditLogMiddleware(
            new modX(['mxheadless_audit_enabled' => false]),
            $repository,
        );

        $middleware->process(
            new ServerRequest('POST', 'https://example.test/api/v1/resources'),
            $this->okHandler(),
        );

        self::assertSame([], $repository->entries);
    }

    public function testLogsMutatingRequestsWhenEnabled(): void
    {
        $repository = new RecordingAuditLogWriter();
        $middleware = new AuditLogMiddleware(
            new modX(['mxheadless_audit_enabled' => true, 'mxheadless_audit_log_get' => false]),
            $repository,
        );

        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources?context=web'))
            ->withHeader('X-Context', 'web')
            ->withAttribute('request_id', 'req-123')
            ->withAttribute('identity_key', 'api_key:abc')
            ->withAttribute('api_key_id', 7);

        $middleware->process($request, $this->okHandler());

        self::assertCount(1, $repository->entries);
        self::assertSame('req-123', $repository->entries[0]['request_id']);
        self::assertSame('POST', $repository->entries[0]['method']);
        self::assertSame('/api/v1/resources', $repository->entries[0]['path']);
        self::assertSame('web', $repository->entries[0]['context_key']);
        self::assertSame(201, $repository->entries[0]['status_code']);
        self::assertSame(7, $repository->entries[0]['api_key_id']);
    }

    public function testSkipsGetUnlessConfigured(): void
    {
        $repository = new RecordingAuditLogWriter();
        $middleware = new AuditLogMiddleware(
            new modX(['mxheadless_audit_enabled' => true, 'mxheadless_audit_log_get' => false]),
            $repository,
        );

        $middleware->process(
            new ServerRequest('GET', 'https://example.test/api/v1/resources'),
            $this->okHandler(),
        );

        self::assertSame([], $repository->entries);
    }

    public function testLogsGetWhenConfigured(): void
    {
        $repository = new RecordingAuditLogWriter();
        $middleware = new AuditLogMiddleware(
            new modX(['mxheadless_audit_enabled' => true, 'mxheadless_audit_log_get' => true]),
            $repository,
        );

        $middleware->process(
            new ServerRequest('GET', 'https://example.test/api/v1/resources'),
            $this->okHandler(),
        );

        self::assertCount(1, $repository->entries);
        self::assertSame('GET', $repository->entries[0]['method']);
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(201, [], '{}');
            }
        };
    }
}

final class RecordingAuditLogWriter implements AuditLogWriter
{
    /** @var list<array<string, mixed>> */
    public array $entries = [];

    /**
     * @param array{
     *     request_id: string,
     *     identity_key: string,
     *     api_key_id: int|null,
     *     method: string,
     *     path: string,
     *     context_key: string,
     *     status_code: int,
     *     duration_ms: int
     * } $entry
     */
    public function append(array $entry): bool
    {
        $this->entries[] = $entry;

        return true;
    }
}
