<?php

declare(strict_types=1);

namespace MxHeadless\Http;

/**
 * Builds URI lookup candidates for /pages/{uri} so Nuxt/Next catch-all
 * routes can omit .html and use index aliases.
 */
final class PageUriResolver
{
    /**
     * @return list<string>
     */
    public static function candidates(string $uri): array
    {
        $uri = trim($uri);
        $hadTrailingSlash = str_ends_with($uri, '/') && $uri !== '/';
        $uri = trim($uri, '/');

        if ($uri === '' || $uri === 'index') {
            return self::unique(['index.html', 'index', '']);
        }

        if ($hadTrailingSlash) {
            return self::unique([$uri . '/', $uri, $uri . '.html']);
        }

        $candidates = [$uri];

        if (!str_contains(basename($uri), '.')) {
            $candidates[] = $uri . '.html';
            $candidates[] = $uri . '/';
        } elseif (str_ends_with(strtolower($uri), '.html')) {
            $candidates[] = substr($uri, 0, -5);
        }

        return self::unique($candidates);
    }

    /**
     * @param list<string> $candidates
     * @return list<string>
     */
    private static function unique(array $candidates): array
    {
        $out = [];
        foreach ($candidates as $candidate) {
            if (!in_array($candidate, $out, true)) {
                $out[] = $candidate;
            }
        }

        return $out;
    }
}
