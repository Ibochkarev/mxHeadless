<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MxHeadless\Exception\ConflictException;
use MxHeadless\Exception\ValidationException;
use MxHeadless\Http\Psr7Factory;
use MxHeadless\Idempotency\IdempotencyFingerprint;
use MxHeadless\Idempotency\IdempotencyStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IdempotencyMiddleware implements MiddlewareInterface
{
    private const KEY_PATTERN = '/^[A-Za-z0-9._:-]+$/';

    public function __construct(
        private readonly IdempotencyStore $store,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->store->isEnabled() || strtoupper($request->getMethod()) !== 'POST') {
            return $handler->handle($request);
        }

        $idempotencyKey = trim($request->getHeaderLine('Idempotency-Key'));
        if ($idempotencyKey === '') {
            return $handler->handle($request);
        }

        if (strlen($idempotencyKey) > 128 || !preg_match(self::KEY_PATTERN, $idempotencyKey)) {
            throw new ValidationException('Invalid Idempotency-Key', [
                'Idempotency-Key' => ['Use 1-128 characters: letters, digits, dot, underscore, colon, hyphen'],
            ]);
        }

        [$request, $payloadHash] = IdempotencyFingerprint::consumePayloadHash($request);
        $fingerprint = IdempotencyFingerprint::fromRequest($request, $idempotencyKey);
        $cached = $this->store->find($fingerprint);
        if ($cached !== null) {
            return $this->replayOrConflict($cached, $payloadHash);
        }

        if (!$this->store->acquire($fingerprint)) {
            $cached = $this->store->find($fingerprint);
            if ($cached !== null) {
                return $this->replayOrConflict($cached, $payloadHash);
            }

            throw new ConflictException('Idempotent request is already in progress');
        }

        try {
            $cached = $this->store->find($fingerprint);
            if ($cached !== null) {
                return $this->replayOrConflict($cached, $payloadHash);
            }

            $response = $handler->handle($request);
            if ($this->shouldCache($response)) {
                $body = (string) $response->getBody();
                $this->store->save(
                    $fingerprint,
                    $idempotencyKey,
                    $response->getStatusCode(),
                    $body,
                    $this->responseHeaders($response, $idempotencyKey),
                    $payloadHash,
                );

                return $this->withFreshBody($response, $body)
                    ->withHeader('Idempotency-Key', $idempotencyKey);
            }

            return $response;
        } finally {
            $this->store->release($fingerprint);
        }
    }

    /**
     * @param array{status_code: int, body: string, headers: array<string, string>, payload_hash: string} $cached
     */
    private function replayOrConflict(array $cached, string $payloadHash): ResponseInterface
    {
        if ($cached['payload_hash'] !== '' && !hash_equals($cached['payload_hash'], $payloadHash)) {
            throw new ConflictException('Idempotency-Key was reused with a different request body');
        }

        return $this->replay($cached);
    }

    private function shouldCache(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();

        return $status >= 200 && $status < 300;
    }

    /**
     * @param array{status_code: int, body: string, headers: array<string, string>, payload_hash?: string} $cached
     */
    private function replay(array $cached): ResponseInterface
    {
        return Psr7Factory::createResponse($cached['status_code'], $cached['headers'], $cached['body'])
            ->withHeader('Idempotency-Replayed', 'true');
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(ResponseInterface $response, string $idempotencyKey): array
    {
        $headers = ['Idempotency-Key' => $idempotencyKey];
        foreach ($response->getHeaders() as $name => $values) {
            if ($values === []) {
                continue;
            }
            $headers[$name] = $values[0];
        }

        return $headers;
    }

    private function withFreshBody(ResponseInterface $response, string $body): ResponseInterface
    {
        return $response->withBody(Psr7Factory::create()->createStream($body));
    }
}
