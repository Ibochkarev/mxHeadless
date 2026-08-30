<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Psr\Http\Message\ResponseInterface;

final class ResponseEmitter
{
    public function emit(ResponseInterface $response): void
    {
        if (headers_sent()) {
            if (!$this->isHeadRequest()) {
                echo (string) $response->getBody();
            }

            return;
        }

        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            $replace = true;
            foreach ($values as $value) {
                header($name . ': ' . $value, $replace);
                $replace = false;
            }
        }

        if ($this->isHeadRequest()) {
            return;
        }

        echo (string) $response->getBody();
    }

    private function isHeadRequest(): bool
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD';
    }
}
