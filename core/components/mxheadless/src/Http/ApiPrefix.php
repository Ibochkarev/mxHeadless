<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;

final class ApiPrefix
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function base(): string
    {
        return rtrim((string) $this->modx->getOption('mxheadless.api.prefix', null, '/api'), '/');
    }

    public function versioned(): string
    {
        return $this->base() . '/v1';
    }

    public function matchesPath(string $path): bool
    {
        $prefix = $this->versioned();

        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }

    public function stripVersionedPrefix(string $path): string
    {
        $prefix = $this->versioned();
        if (!str_starts_with($path, $prefix)) {
            return $path;
        }

        $relative = substr($path, strlen($prefix));

        return $relative === '' ? '/' : $relative;
    }
}
