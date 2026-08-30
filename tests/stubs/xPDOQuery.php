<?php

declare(strict_types=1);

/**
 * Minimal xPDOQuery stub for unit tests (no MODX runtime required).
 */
class xPDOQuery
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $conditions = [];

    /** @var list<array<string, mixed>> */
    public array $wheres = [];

    /** @var list<array{0: string, 1: string}> */
    public array $sorts = [];

    /** @var list<string> */
    public array $selects = [];

    public ?int $limitValue = null;
    public ?int $offsetValue = null;

    public function __construct(
        public readonly string $class,
        public readonly string $alias = '',
    ) {
    }

    public function select(array $columns): self
    {
        $this->selects = array_merge($this->selects, $columns);

        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function andCondition(string|array $condition, array $params = []): self
    {
        if (is_array($condition)) {
            foreach ($condition as $sql => $bound) {
                $this->conditions[] = [$sql, is_array($bound) ? $bound : []];
            }
        } else {
            $this->conditions[] = [$condition, $params];
        }

        return $this;
    }

    public function sortby(string $column, string $direction = 'ASC'): self
    {
        $this->sorts[] = [$column, strtoupper($direction)];

        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limitValue = $limit;
        $this->offsetValue = $offset;

        return $this;
    }

    /**
     * @param array<string, mixed>|string $criteria
     * @return list<array<string, mixed>>
     */
    public function where(array|string $criteria = []): array
    {
        if (is_array($criteria) && $criteria !== []) {
            $this->wheres[] = $criteria;
            $this->conditions[] = [json_encode($criteria, JSON_THROW_ON_ERROR), $criteria];
        }

        return $this->wheres;
    }
}
