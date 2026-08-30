<?php

declare(strict_types=1);

namespace MODX\Revolution;

use xPDOQuery;

/**
 * Minimal modX stub for unit tests and static analysis.
 */
class modX
{
    public const LOG_LEVEL_ERROR = 1;

    /** @var object|null */
    public ?object $context = null;

    /** @var object */
    public object $services;

    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->context = new class {
            public function get(string $key): mixed
            {
                return match ($key) {
                    'key' => 'web',
                    default => null,
                };
            }
        };
        $this->services = new class {
            /** @var array<string, object> */
            private array $services = [];

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }

            public function get(string $id): object
            {
                return $this->services[$id];
            }
        };
    }

    public function getOption(string $key, $options = null, $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function newQuery(string $class, $criteria = null, bool $cacheFlag = true, string $alias = ''): xPDOQuery
    {
        return new xPDOQuery($class, $alias);
    }

  /**
   * @param array<string, mixed>|int|string $criteria
   */
    public function getObject(string $class, $criteria): ?\xPDOObject
    {
        return null;
    }

    public function newObject(string $class, ?array $data = null): ?\xPDOObject
    {
        return new \xPDOObject();
    }

    /**
     * @param array<string, mixed>|int|string|xPDOQuery|null $criteria
     * @return list<\xPDOObject>
     */
    public function getCollection(
        string $class,
        mixed $criteria = null,
        bool $cacheFlag = true,
        bool $includeRelated = false,
        mixed $sort = '',
        int $limit = 0,
        int $offset = 0,
    ): array {
        return [];
    }

    public function removeCollection(string $class, mixed $criteria = null): int
    {
        return 0;
    }

    public function getTableName(string $class): string
    {
        return 'modx_mxheadless_api_log';
    }

    public function getValue(xPDOQuery $query): mixed
    {
        return 0;
    }

    public function getCount(string $class, mixed $criteria = null): int
    {
        return 0;
    }

    public function hasPermission(string $permission): bool
    {
        return false;
    }

    public function getAuthenticatedUser(): ?object
    {
        return null;
    }

    /** @var object|null */
    private ?object $cacheManager = null;

    public function getCacheManager(): object
    {
        if ($this->cacheManager !== null) {
            return $this->cacheManager;
        }

        $this->cacheManager = new class {
            /** @var array<string, mixed> */
            private array $store = [];

            private function storageKey(string $key, array $options): string
            {
                $namespace = is_string($options[0] ?? null) ? $options[0] : 'default';

                return $namespace . "\0" . $key;
            }

            public function get(string $key, array $options = []): mixed
            {
                $storageKey = $this->storageKey($key, $options);

                return $this->store[$storageKey] ?? null;
            }

            public function set(string $key, mixed $value, int $lifetime = 0, array $options = []): void
            {
                $this->store[$this->storageKey($key, $options)] = $value;
            }

            public function add(string $key, mixed &$var, int $lifetime = 0, array $options = []): bool
            {
                $storageKey = $this->storageKey($key, $options);
                if (array_key_exists($storageKey, $this->store)) {
                    return false;
                }
                $this->store[$storageKey] = $var;

                return true;
            }

            public function delete(string $key, array $options = []): void
            {
                unset($this->store[$this->storageKey($key, $options)]);
            }
        };

        return $this->cacheManager;
    }

    public function query(string $sql): mixed
    {
        return true;
    }

    public function getContext(string $key): ?object
    {
        return new class ($key) {
            public function __construct(private readonly string $key)
            {
            }

            public function getOption(string $option, mixed $default = null, mixed $fallback = ''): mixed
            {
                return match ($option) {
                    'site_url' => '/' . $this->key . '/',
                    'base_url' => '/' . $this->key . '/',
                    'http_host' => $this->key . '.example.test',
                    'site_start' => '1',
                    default => $fallback,
                };
            }
        };
    }

    /**
     * @param array<string, mixed> $params
     */
    /** @var object|null */
    public ?object $Event = null;

    public function invokeEvent(string $eventName, array $params = []): void
    {
        $this->Event = new class {
            /** @var array<string, mixed> */
            public array $returned = [];
        };
    }
}
