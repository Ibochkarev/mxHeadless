<?php

declare(strict_types=1);

namespace MxHeadless\Query;

final class Sort
{
    public function __construct(
        private readonly string $field,
        private readonly string $direction = 'ASC',
    ) {
    }

    public function field(): string
    {
        return $this->field;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function isAscending(): bool
    {
        return strtoupper($this->direction) === 'ASC';
    }
}
