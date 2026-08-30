<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class ServiceDisabledException extends HttpException
{
    public function __construct(string $message = 'API is temporarily disabled')
    {
        parent::__construct(
            $message,
            503,
            'Service Unavailable',
            'https://mxheadless.dev/problems/service-disabled',
            [],
            'service_disabled',
        );
    }
}
