<?php

declare(strict_types=1);

/**
 * Resolve the MODX site root for CLI bins.
 * Works for installed packages (core/components/mxheadless/bin)
 * and the Extra source tree (Extras/.../core/components/mxheadless/bin).
 *
 * @return non-empty-string
 */
function mxheadlessResolveCliModxRoot(): string
{
    $path = dirname(__DIR__);
    for ($i = 0; $i < 10; ++$i) {
        $path = dirname($path);
        if ($path === '/' || $path === '' || $path === '.') {
            break;
        }
        if (is_file($path . '/core/config/config.inc.php')) {
            return $path;
        }
    }

    fwrite(STDERR, "MODX core/config/config.inc.php not found above " . dirname(__DIR__) . "\n");
    exit(1);
}
