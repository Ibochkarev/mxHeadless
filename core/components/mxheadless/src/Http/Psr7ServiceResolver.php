<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;

final class Psr7ServiceResolver
{
    /**
     * @template T of object
     * @param class-string<T> $interface
     * @return T
     */
    public static function resolve(modX $modx, string $interface): object
    {
        if ($modx->services->has($interface)) {
            return $modx->services->get($interface);
        }

        return Psr7Factory::create();
    }
}
