<?php

declare(strict_types=1);

namespace MxHeadless\Query;

final class Filter
{
    public function __construct(
        private readonly string $field,
        private readonly FilterOperator $operator,
        private readonly mixed $value = null,
    ) {
    }

    public function field(): string
    {
        return $this->field;
    }

    public function operator(): FilterOperator
    {
        return $this->operator;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
