<?php

declare(strict_types=1);

namespace MxHeadless\Definition;

final class ObjectDefinition
{
    /** @var list<string> */
    private array $fields = [];

    /** @var list<string> */
    private array $filterable = [];

    /** @var list<string> */
    private array $sortable = [];

    /** @var list<string> */
    private array $searchable = [];

    /** @var list<RelationDefinition> */
    private array $relations = [];

    /** @var list<string> */
    private array $hiddenFields = [];

    /** @var list<string> */
    private array $protectedFields = [];

    /** @var list<string> */
    private array $immutableFields = [];

    /** @var list<string> */
    private array $requiredFields = [];

    /** @var list<string> */
    private array $contexts = [];

    private string $primaryKey = 'id';
    private bool $contextAccessGated = false;

    private bool $readable = false;
    private bool $creatable = false;
    private bool $updatable = false;
    private bool $deletable = false;

    public function __construct(
        private readonly string $name = '',
        private readonly string $className = '',
    ) {
    }

    public static function create(?string $name = null): self
    {
        return new self($name ?? '');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function objectClass(): string
    {
        return $this->className;
    }

    /**
     * @return list<string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return list<string>
     */
    public function getFilterable(): array
    {
        return $this->filterable;
    }

    /**
     * @return list<string>
     */
    public function getSortable(): array
    {
        return $this->sortable;
    }

    /**
     * @return list<string>
     */
    public function getSearchable(): array
    {
        return $this->searchable;
    }

    /**
     * @return list<RelationDefinition>
     */
    public function relations(): array
    {
        return $this->relations;
    }

    public function getRelation(string $name): ?RelationDefinition
    {
        foreach ($this->relations as $relation) {
            if ($relation->name() === $name) {
                return $relation;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function getHiddenFields(): array
    {
        return $this->hiddenFields;
    }

    /**
     * @return list<string>
     */
    public function getProtectedFields(): array
    {
        return $this->protectedFields;
    }

    /**
     * @return list<string>
     */
    public function getImmutableFields(): array
    {
        return $this->immutableFields;
    }

    /**
     * @return list<string>
     */
    public function getRequiredFields(): array
    {
        return $this->requiredFields;
    }

    /**
     * @return list<string>
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isCreatable(): bool
    {
        return $this->creatable;
    }

    public function isUpdatable(): bool
    {
        return $this->updatable;
    }

    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function isContextAccessGated(): bool
    {
        return $this->contextAccessGated;
    }

    public function setName(string $name): self
    {
        return $this->with(['name' => $name]);
    }

    public function class(string $class): self
    {
        return $this->with(['className' => $class]);
    }

    /**
     * @param list<string> $fields
     */
    public function fields(array $fields): self
    {
        return $this->with(['fields' => array_values($fields)]);
    }

    /**
     * @param list<string> $fields
     */
    public function filterable(array $fields): self
    {
        return $this->with(['filterable' => array_values($fields)]);
    }

    /**
     * @param list<string> $fields
     */
    public function sorts(array $fields): self
    {
        return $this->with(['sortable' => array_values($fields)]);
    }

    /**
     * @param list<string> $fields
     */
    public function searchable(array $fields): self
    {
        return $this->with(['searchable' => array_values($fields)]);
    }

    public function relation(RelationDefinition $relation): self
    {
        $relations = $this->relations;
        foreach ($relations as $index => $existing) {
            if ($existing->name() === $relation->name()) {
                $relations[$index] = $relation;

                return $this->with(['relations' => $relations]);
            }
        }
        $relations[] = $relation;

        return $this->with(['relations' => $relations]);
    }

    public function readable(bool $readable = true): self
    {
        return $this->with(['readable' => $readable]);
    }

    public function creatable(bool $creatable = true): self
    {
        return $this->with(['creatable' => $creatable]);
    }

    public function updatable(bool $updatable = true): self
    {
        return $this->with(['updatable' => $updatable]);
    }

    public function deletable(bool $deletable = true): self
    {
        return $this->with(['deletable' => $deletable]);
    }

    /**
     * @param list<string> $fields
     */
    public function hiddenFields(array $fields): self
    {
        return $this->with(['hiddenFields' => array_values($fields)]);
    }

    /**
     * @param list<string> $fields
     */
    public function protectedFields(array $fields): self
    {
        return $this->with(['protectedFields' => array_values($fields)]);
    }

    /**
     * @param list<string> $fields
     */
    public function immutableFields(array $fields): self
    {
        return $this->with(['immutableFields' => array_values($fields)]);
    }

    /**
     * Fields that must be present and non-empty on create.
     *
     * @param list<string> $fields
     */
    public function requiredFields(array $fields): self
    {
        return $this->with(['requiredFields' => array_values($fields)]);
    }

    /**
     * @param list<string> $contexts
     */
    public function contexts(array $contexts): self
    {
        return $this->with(['contexts' => array_values($contexts)]);
    }

    public function primaryKey(string $field): self
    {
        return $this->with(['primaryKey' => $field]);
    }

    public function contextAccessGated(bool $gated = true): self
    {
        return $this->with(['contextAccessGated' => $gated]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function with(array $overrides): self
    {
        $clone = new self(
            $overrides['name'] ?? $this->name,
            $overrides['className'] ?? $this->className,
        );
        $clone->fields = $overrides['fields'] ?? $this->fields;
        $clone->filterable = $overrides['filterable'] ?? $this->filterable;
        $clone->sortable = $overrides['sortable'] ?? $this->sortable;
        $clone->searchable = $overrides['searchable'] ?? $this->searchable;
        $clone->relations = $overrides['relations'] ?? $this->relations;
        $clone->hiddenFields = $overrides['hiddenFields'] ?? $this->hiddenFields;
        $clone->protectedFields = $overrides['protectedFields'] ?? $this->protectedFields;
        $clone->immutableFields = $overrides['immutableFields'] ?? $this->immutableFields;
        $clone->requiredFields = $overrides['requiredFields'] ?? $this->requiredFields;
        $clone->contexts = $overrides['contexts'] ?? $this->contexts;
        $clone->primaryKey = $overrides['primaryKey'] ?? $this->primaryKey;
        $clone->contextAccessGated = $overrides['contextAccessGated'] ?? $this->contextAccessGated;
        $clone->readable = $overrides['readable'] ?? $this->readable;
        $clone->creatable = $overrides['creatable'] ?? $this->creatable;
        $clone->updatable = $overrides['updatable'] ?? $this->updatable;
        $clone->deletable = $overrides['deletable'] ?? $this->deletable;

        return $clone;
    }
}
