<?php

declare(strict_types=1);

namespace xPDO\Transport;

use MODX\Revolution\modX;

/**
 * Minimal xPDO transport stub for resolver static analysis.
 */
class xPDOTransport
{
    public const PACKAGE_ACTION = 'package_install';
    public const ACTION_INSTALL = 'install';
    public const ACTION_UPGRADE = 'upgrade';
    public const ACTION_UNINSTALL = 'uninstall';

    public ?modX $xpdo = null;

    public ?string $signature = null;
}
