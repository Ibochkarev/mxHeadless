<?php

declare(strict_types=1);

namespace MxHeadless\Registry;

use MxHeadless\Definition\ObjectDefinition;

final class ObjectRegistry
{
    /** @var array<string, ObjectDefinition> */
    private array $objects = [];

    private bool $frozen = false;

    public function register(ObjectDefinition $definition): void
    {
        if ($this->frozen) {
            throw new RegistryFrozenException();
        }

        $name = $definition->name();
        if ($name === '') {
            throw new \InvalidArgumentException('Object definition requires a name');
        }

        $this->objects[$name] = $definition;
    }

    public function get(string $name): ?ObjectDefinition
    {
        return $this->objects[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->objects[$name]);
    }

    /**
     * @return array<string, ObjectDefinition>
     */
    public function all(): array
    {
        return $this->objects;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }
}
