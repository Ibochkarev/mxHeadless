<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use MODX\Revolution\modX;

/**
 * Resolves the MODX context used to bootstrap the API (setting mxheadless_context).
 */
final class ApiContext
{
    public static function bootstrapKey(modX $modx): string
    {
        $contextKey = trim((string) $modx->getOption('mxheadless_context', null, 'web'));
        if ($contextKey === '' || strcasecmp($contextKey, 'mgr') === 0) {
            return 'web';
        }

        return $contextKey;
    }

    public static function apply(modX $modx): string
    {
        $target = self::bootstrapKey($modx);
        $current = (string) ($modx->context?->get('key') ?? '');
        if ($current !== $target) {
            $modx->switchContext($target);
        }

        return $target;
    }
}
