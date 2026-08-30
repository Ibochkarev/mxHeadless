<?php

declare(strict_types=1);

namespace MxHeadless\Exception;

use MxHeadless\Http\ProblemDetails;
use Psr\Http\Message\ResponseInterface;

class HttpException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $extra
     * @param array<string, string> $headers
     */
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        private readonly string $title = 'Error',
        private readonly string $type = 'about:blank',
        private readonly array $extra = [],
        private readonly ?string $errorCode = null,
        private readonly array $headers = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function toResponse(string $instance = ''): ResponseInterface
    {
        $extra = $this->extra;
        if ($this->errorCode !== null && $this->errorCode !== '') {
            $extra['code'] = $this->errorCode;
        }

        $response = ProblemDetails::response(
            $this->type,
            $this->title,
            $this->statusCode,
            $this->getMessage(),
            $instance,
            $extra,
        );

        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
