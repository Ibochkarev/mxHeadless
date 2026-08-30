<?php

declare(strict_types=1);

namespace MxHeadless\Authentication;

final class CredentialParser
{
    /**
     * @return array{0: string, 1: string}|null
     */
    public static function splitPrefixedToken(string $token, string $prefix): ?array
    {
        if (!str_starts_with($token, $prefix)) {
            return null;
        }

        $parts = explode('_', $token, 3);
        if (count($parts) !== 3) {
            return null;
        }

        [, $publicId, $secret] = $parts;
        if ($publicId === '' || $secret === '') {
            return null;
        }

        return [$publicId, $secret];
    }

    /**
     * @return list<string>
     */
    public static function scopeList(mixed $scopes): array
    {
        if (is_array($scopes)) {
            return array_values(array_filter(array_map('strval', $scopes)));
        }

        if (!is_string($scopes) || trim($scopes) === '') {
            return [];
        }

        $decoded = json_decode($scopes, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $scopes))));
    }

    /**
     * @return list<string>
     */
    public static function grantTypes(mixed $grantTypes): array
    {
        $parsed = self::scopeList($grantTypes);

        return $parsed !== [] ? $parsed : ['client_credentials'];
    }

    public static function positiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = (int) $value;

        return $parsed > 0 ? $parsed : null;
    }
}
