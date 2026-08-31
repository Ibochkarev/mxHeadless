<?php

declare(strict_types=1);

/**
 * Legacy MODX alias used in transport resolvers.
 */
class modX extends MODX\Revolution\modX
{
    public const LOG_LEVEL_ERROR = 1;
    public const LOG_LEVEL_INFO = 2;

    public function reloadConfig(): void
    {
    }

    public function getVersionData(): void
    {
    }

    /**
     * @return object{pdo?: PDO|null}
     */
    public function getConnection(): object
    {
        return new class {
            public ?PDO $pdo = null;
        };
    }
}
