<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct(
            $message,
            404,
            'Not Found',
            'https://mxheadless.dev/problems/not-found',
            [],
            'not_found',
        );
    }
}
