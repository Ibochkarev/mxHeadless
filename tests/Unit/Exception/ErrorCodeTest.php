<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Exception;

use MxHeadless\Exception\ConflictException;
use MxHeadless\Exception\ForbiddenException;
use MxHeadless\Exception\NotFoundException;
use MxHeadless\Exception\ServiceDisabledException;
use MxHeadless\Exception\UnauthorizedException;
use MxHeadless\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class ErrorCodeTest extends TestCase
{
    public function testUnauthorizedHasTokenRequiredCode(): void
    {
        $response = UnauthorizedException::missing()->toResponse('/api/v1/resources');
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('token_required', $body['code']);
    }

    public function testUnauthorizedInvalidTokenCode(): void
    {
        $response = UnauthorizedException::invalid()->toResponse('/api/v1/contexts');
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('invalid_token', $body['code']);
        self::assertSame('Invalid or revoked credentials', $body['detail']);
    }

    public function testForbiddenHasScopeDeniedCode(): void
    {
        $body = json_decode((string) (new ForbiddenException())->toResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('scope_denied', $body['code']);
    }

    public function testConflictHasIdempotencyConflictCode(): void
    {
        $body = json_decode((string) (new ConflictException())->toResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('idempotency_conflict', $body['code']);
        self::assertSame(409, $body['status']);
    }

    public function testServiceDisabledHasCode(): void
    {
        $body = json_decode((string) (new ServiceDisabledException())->toResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('service_disabled', $body['code']);
        self::assertSame(503, $body['status']);
    }

    public function testNotFoundHasCode(): void
    {
        $body = json_decode((string) (new NotFoundException())->toResponse('/api/v1/x')->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('not_found', $body['code']);
        self::assertSame(404, $body['status']);
    }

    public function testValidationHasCode(): void
    {
        $body = json_decode(
            (string) (new ValidationException('bad', ['fields' => ['unknown']]))->toResponse()->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('validation_failed', $body['code']);
        self::assertSame(['fields' => ['unknown']], $body['errors']);
    }
}
