<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal PSR-18 client for webhook delivery when the host has not registered
 * another ClientInterface in the MODX container.
 *
 * Uses PHP HTTP streams (not libcurl) so connect/read timeouts are enforced
 * under PHP-FPM, where SIGALRM-based curl timeouts are unreliable.
 */
final class CurlHttpClient implements ClientInterface
{
    private const CONNECT_AND_READ_TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly bool $verifyPeer = true,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $uri = (string) $request->getUri();
        $scheme = strtolower((string) $request->getUri()->getScheme());
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw $this->clientException('Only http/https webhook URLs are allowed');
        }

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }

        $body = (string) $request->getBody();
        $context = stream_context_create([
            'http' => [
                'method' => $request->getMethod(),
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => self::CONNECT_AND_READ_TIMEOUT_SECONDS,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
            'ssl' => [
                'verify_peer' => $this->verifyPeer,
                'verify_peer_name' => $this->verifyPeer,
            ],
        ]);

        $raw = @file_get_contents($uri, false, $context);
        if ($raw === false) {
            $error = error_get_last();
            $message = is_array($error) ? $error['message'] : '';
            throw $this->clientException($message !== '' ? $message : 'HTTP request failed');
        }

        $headerLines = function_exists('http_get_last_response_headers')
            ? http_get_last_response_headers()
            : null;
        if (!is_array($headerLines)) {
            $headerLines = [];
        }

        $responseHeaders = [];
        $status = 0;
        foreach ($headerLines as $line) {
            if (!is_string($line)) {
                continue;
            }
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $matches) === 1) {
                $status = (int) $matches[1];
                continue;
            }
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[trim($name)] = trim($value);
        }

        return Psr7Factory::createResponse($status > 0 ? $status : 0, $responseHeaders, $raw);
    }

    private function clientException(string $message): ClientExceptionInterface
    {
        return new class ($message) extends \RuntimeException implements ClientExceptionInterface {
        };
    }
}
