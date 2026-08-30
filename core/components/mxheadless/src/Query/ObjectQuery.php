<?php

declare(strict_types=1);

namespace MxHeadless\Query;

final class ObjectQuery
{
    /**
     * @param list<Filter> $filters
     * @param list<Sort> $sorts
     */
    public function __construct(
        private readonly string $objectName,
        private readonly array $filters = [],
        private readonly array $sorts = [],
        private readonly Pagination $pagination = new Pagination(),
        private readonly FieldSelection $fields = new FieldSelection(),
        private readonly IncludeTree $includes = new IncludeTree(),
        private readonly ?string $search = null,
        private readonly string $context = 'web',
        private readonly bool $preview = false,
        private readonly bool $includeDeleted = false,
    ) {
    }

    public function objectName(): string
    {
        return $this->objectName;
    }

    /**
     * @return list<Filter>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @return list<Sort>
     */
    public function sorts(): array
    {
        return $this->sorts;
    }

    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    public function fields(): FieldSelection
    {
        return $this->fields;
    }

    public function includes(): IncludeTree
    {
        return $this->includes;
    }

    public function search(): ?string
    {
        return $this->search;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function preview(): bool
    {
        return $this->preview;
    }

    public function includeDeleted(): bool
    {
        return $this->includeDeleted;
    }
}
