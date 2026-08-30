<?php

declare(strict_types=1);

namespace MxHeadless\Webhook;

final class WebhookSigner
{
    public function sign(string $secret, string $payload, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signedPayload = $timestamp . '.' . $payload;

        return 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $signedPayload, $secret);
    }

    public function verify(string $secret, string $payload, string $signatureHeader): bool
    {
        $timestamp = null;
        $signature = null;
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = (int) $value;
            }
            if ($key === 'v1') {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null) {
            return false;
        }

        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
