<?php

declare(strict_types=1);

namespace MxHeadless\Query;

final class Pagination
{
    public function __construct(
        private readonly int $limit = 20,
        private readonly int $offset = 0,
    ) {
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function offset(): int
    {
        return $this->offset;
    }
}
