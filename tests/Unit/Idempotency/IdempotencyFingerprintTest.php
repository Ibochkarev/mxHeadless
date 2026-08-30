<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Idempotency;

use MxHeadless\Authentication\AnonymousPermissionChecker;
use MxHeadless\Authentication\Identity;
use MxHeadless\Idempotency\IdempotencyFingerprint;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class IdempotencyFingerprintTest extends TestCase
{
    public function testFingerprintScopesActorPathAndKey(): void
    {
        $identity = new Identity(Identity::TYPE_API_KEY, 'key-abc', new AnonymousPermissionChecker());

        $request = (new ServerRequest('POST', 'https://example.test/api/v1/resources'))
            ->withAttribute('identity', $identity);

        $first = IdempotencyFingerprint::fromRequest($request, 'idem-1');
        $second = IdempotencyFingerprint::fromRequest($request, 'idem-2');
        $anonymous = IdempotencyFingerprint::fromRequest(new ServerRequest('POST', 'https://example.test/api/v1/resources'), 'idem-1');

        self::assertNotSame($first, $second);
        self::assertNotSame($first, $anonymous);
    }

    public function testPayloadHashDiffersForDifferentBodiesAndRewindsStream(): void
    {
        $request = new ServerRequest('POST', 'https://example.test/api/v1/resources', [], '{"a":1}');
        [$rewound, $hashA] = IdempotencyFingerprint::consumePayloadHash($request);
        self::assertSame('{"a":1}', (string) $rewound->getBody());

        $other = new ServerRequest('POST', 'https://example.test/api/v1/resources', [], '{"a":2}');
        [, $hashB] = IdempotencyFingerprint::consumePayloadHash($other);

        self::assertNotSame($hashA, $hashB);
    }
}
