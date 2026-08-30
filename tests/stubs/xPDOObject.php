<?php

declare(strict_types=1);

/**
 * Global xPDOObject stub for static analysis and unit tests.
 */
class xPDOObject
{
    /** @var array<string, mixed> */
    protected array $fields = [];

    public function get(string $key): mixed
    {
        return $this->fields[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->fields[$key] = $value;
    }

    public function save(?string $cacheFlag = null): bool
    {
        return true;
    }

    public function remove(?string $cacheFlag = null): bool
    {
        return true;
    }

    public function toArray(?string $keyPrefix = null, bool $rawValues = false, bool $excludeLazy = false, bool $includeRelated = false): array
    {
        return $this->fields;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data, ?string $keyPrefix = null): bool
    {
        foreach ($data as $key => $value) {
            $this->fields[$key] = $value;
        }

        return true;
    }
}
