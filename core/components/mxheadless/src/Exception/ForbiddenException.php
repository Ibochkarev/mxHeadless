<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct(
            $message,
            403,
            'Forbidden',
            'https://mxheadless.dev/problems/forbidden',
            [],
            'scope_denied',
        );
    }
}
