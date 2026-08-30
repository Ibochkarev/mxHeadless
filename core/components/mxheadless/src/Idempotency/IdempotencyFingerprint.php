<?php

declare(strict_types=1);

namespace MxHeadless\Idempotency;

use MxHeadless\Authentication\Identity;
use MxHeadless\Http\Psr7Factory;
use Psr\Http\Message\ServerRequestInterface;

final class IdempotencyFingerprint
{
    public static function fromRequest(ServerRequestInterface $request, string $idempotencyKey): string
    {
        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        $actor = $identity?->key() ?? Identity::TYPE_ANONYMOUS;
        $path = parse_url((string) $request->getUri(), PHP_URL_PATH) ?: '/';

        return hash('sha256', $actor . '|' . $path . '|' . $idempotencyKey);
    }

    /**
     * Hash of method + raw body. Rewinds/replaces the body stream so downstream can read it.
     *
     * @return array{0: ServerRequestInterface, 1: string}
     */
    public static function consumePayloadHash(ServerRequestInterface $request): array
    {
        $body = (string) $request->getBody();
        $hash = hash('sha256', strtoupper($request->getMethod()) . '|' . $body);

        $stream = $request->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        } else {
            $request = $request->withBody(Psr7Factory::create()->createStream($body));
        }

        return [$request, $hash];
    }
}
