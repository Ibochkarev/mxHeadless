<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

final class ValidationException extends HttpException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(string $message = 'Validation failed', array $errors = [])
    {
        parent::__construct(
            $message,
            422,
            'Unprocessable Entity',
            'https://mxheadless.dev/problems/validation',
            ['errors' => $errors],
            'validation_failed',
        );
    }
}
