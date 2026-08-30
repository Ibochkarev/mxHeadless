<?php

declare(strict_types=1);

namespace MxHeadless\Query;

enum FilterOperator: string
{
    case Eq = 'eq';
    case Neq = 'neq';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case Like = 'like';
    case In = 'in';
    case NotIn = 'not_in';
    case Null = 'null';
    case NotNull = 'not_null';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new \InvalidArgumentException('Unknown filter operator: ' . $value);
    }
}
