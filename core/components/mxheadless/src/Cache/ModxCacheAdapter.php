<?php

declare(strict_types=1);

namespace MxHeadless\Cache;

use MODX\Revolution\modX;

final class ModxCacheAdapter
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function get(string $key, string $namespace = 'default'): mixed
    {
        return $this->modx->getCacheManager()->get($key, [$this->resolveNamespace($namespace)]);
    }

    public function set(string $key, mixed $value, int $ttl, string $namespace = 'default'): void
    {
        $this->modx->getCacheManager()->set($key, $value, $ttl, [$this->resolveNamespace($namespace)]);
    }

    /**
     * Atomically store a value only if the key is missing.
     */
    public function add(string $key, mixed $value, int $ttl, string $namespace = 'default'): bool
    {
        $var = $value;

        return (bool) $this->modx->getCacheManager()->add($key, $var, $ttl, [$this->resolveNamespace($namespace)]);
    }

    public function delete(string $key, string $namespace = 'default'): void
    {
        $this->modx->getCacheManager()->delete($key, [$this->resolveNamespace($namespace)]);
    }

    public function getTagVersion(string $tag): int
    {
        return (int) ($this->get('version/' . $tag, 'tags') ?: 0);
    }

    public function invalidateTag(string $tag): void
    {
        $this->set('version/' . $tag, $this->getTagVersion($tag) + 1, 0, 'tags');
    }

    private function resolveNamespace(string $namespace): string
    {
        return 'mxheadless/' . trim($namespace, '/');
    }
}
