<?php

declare(strict_types=1);

namespace MxHeadless\Query;

final class FieldSelection
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        private readonly array $fields = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function isEmpty(): bool
    {
        return $this->fields === [];
    }
}
