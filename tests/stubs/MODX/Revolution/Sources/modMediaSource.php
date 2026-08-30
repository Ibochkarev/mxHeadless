<?php

declare(strict_types=1);

namespace MODX\Revolution\Sources;

class modMediaSource extends \xPDOObject
{
    public function initialize(): bool
    {
        return true;
    }

    public function getObjectUrl(string $object): string
    {
        return $object;
    }
}
