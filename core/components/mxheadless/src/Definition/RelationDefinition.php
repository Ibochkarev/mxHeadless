<?php

declare(strict_types=1);

namespace MxHeadless\Definition;

final class RelationDefinition
{
    public const TYPE_TO_ONE = 'to_one';
    public const TYPE_TO_MANY = 'to_many';

    /**
     * @param list<string> $relationFields
     */
    public function __construct(
        private readonly string $name,
        private readonly string $targetObject,
        private readonly string $relationType = self::TYPE_TO_ONE,
        private readonly ?string $foreignKey = null,
        private readonly ?string $localKey = null,
        private readonly array $relationFields = [],
        private readonly bool $allowRead = true,
        private readonly ?int $depthLimit = null,
    ) {
    }

    public static function create(string $name): self
    {
        return new self($name, '');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function targetObject(): string
    {
        return $this->targetObject;
    }

    public function type(): string
    {
        return $this->relationType;
    }

    public function foreignKey(): ?string
    {
        return $this->foreignKey;
    }

    public function localKey(): ?string
    {
        return $this->localKey;
    }

    /**
     * @return list<string>
     */
    public function getFields(): array
    {
        return $this->relationFields;
    }

    public function isReadable(): bool
    {
        return $this->allowRead;
    }

    public function getMaxDepth(): ?int
    {
        return $this->depthLimit;
    }

    public function to(string $targetObject): self
    {
        return $this->with(['targetObject' => $targetObject]);
    }

    public function toOne(): self
    {
        return $this->with(['relationType' => self::TYPE_TO_ONE]);
    }

    public function toMany(): self
    {
        return $this->with(['relationType' => self::TYPE_TO_MANY]);
    }

    public function foreignKeyField(string $foreignKey): self
    {
        return $this->with(['foreignKey' => $foreignKey]);
    }

    public function localKeyField(string $localKey): self
    {
        return $this->with(['localKey' => $localKey]);
    }

    /**
     * @param list<string> $fields
     */
    public function fields(array $fields): self
    {
        return $this->with(['relationFields' => array_values($fields)]);
    }

    public function readable(bool $readable = true): self
    {
        return $this->with(['allowRead' => $readable]);
    }

    public function maxDepth(int $depth): self
    {
        return $this->with(['depthLimit' => $depth]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function with(array $overrides): self
    {
        return new self(
            $overrides['name'] ?? $this->name,
            $overrides['targetObject'] ?? $this->targetObject,
            $overrides['relationType'] ?? $this->relationType,
            $overrides['foreignKey'] ?? $this->foreignKey,
            $overrides['localKey'] ?? $this->localKey,
            $overrides['relationFields'] ?? $this->relationFields,
            $overrides['allowRead'] ?? $this->allowRead,
            $overrides['depthLimit'] ?? $this->depthLimit,
        );
    }
}
