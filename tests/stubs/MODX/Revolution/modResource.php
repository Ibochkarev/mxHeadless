<?php

declare(strict_types=1);

namespace MODX\Revolution;

class modResource extends \xPDOObject
{
    /**
     * @return list<\xPDOObject>|false
     */
    public function getMany(string $alias): array|false
    {
        return [];
    }
}
