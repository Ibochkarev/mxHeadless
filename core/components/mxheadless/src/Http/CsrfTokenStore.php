<?php

declare(strict_types=1);

namespace MxHeadless\Http;

/**
 * Session CSRF token for Manager / web session mutations.
 */
final class CsrfTokenStore
{
    public const SESSION_KEY = 'mxheadless.csrf_token';
    public const HEADER = 'X-CSRF-Token';

    public static function ensure(): string
    {
        $existing = self::peek();
        if ($existing !== '') {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    public static function peek(): string
    {
        $value = $_SESSION[self::SESSION_KEY] ?? '';

        return is_string($value) ? $value : '';
    }
}
