<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class InvalidGrantException extends HttpException
{
    public function __construct(string $message = 'Invalid grant')
    {
        parent::__construct(
            $message,
            400,
            'Bad Request',
            'https://mxheadless.dev/problems/invalid-grant',
            [],
            'invalid_grant',
        );
    }
}
