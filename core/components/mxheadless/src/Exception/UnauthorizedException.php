<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class UnauthorizedException extends HttpException
{
    public const CODE_TOKEN_REQUIRED = 'token_required';

    public const CODE_INVALID_TOKEN = 'invalid_token';

    public function __construct(
        string $message = 'Unauthorized',
        string $code = self::CODE_TOKEN_REQUIRED,
    ) {
        parent::__construct(
            $message,
            401,
            'Unauthorized',
            'https://mxheadless.dev/problems/unauthorized',
            [],
            $code,
        );
    }

    public static function missing(): self
    {
        return new self('Authentication required', self::CODE_TOKEN_REQUIRED);
    }

    public static function invalid(): self
    {
        return new self('Invalid or revoked credentials', self::CODE_INVALID_TOKEN);
    }
}
