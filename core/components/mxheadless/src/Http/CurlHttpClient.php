<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal PSR-18 client backed by curl for webhook delivery when the host
 * has not registered another ClientInterface in the MODX container.
 */
final class CurlHttpClient implements ClientInterface
{
    public function __construct(
        private readonly bool $verifyPeer = true,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (!function_exists('curl_init')) {
            throw $this->clientException('curl extension is required for webhook delivery');
        }

        $handle = curl_init((string) $request->getUri());
        if ($handle === false) {
            throw $this->clientException('Unable to initialize curl');
        }

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }

        $body = (string) $request->getBody();
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => $this->verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $this->verifyPeer ? 2 : 0,
        ]);

        if ($body !== '') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($handle);
        if ($raw === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw $this->clientException($error !== '' ? $error : 'curl request failed');
        }

        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        curl_close($handle);

        $headerRaw = substr($raw, 0, $headerSize);
        $responseBody = substr($raw, $headerSize);
        $responseHeaders = [];
        foreach (explode("\r\n", $headerRaw) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[trim($name)] = trim($value);
        }

        return Psr7Factory::createResponse($status, $responseHeaders, $responseBody);
    }

    private function clientException(string $message): ClientExceptionInterface
    {
        return new class ($message) extends \RuntimeException implements ClientExceptionInterface {
        };
    }
}
