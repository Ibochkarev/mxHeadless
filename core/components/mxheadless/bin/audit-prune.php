<?php

declare(strict_types=1);

/**
 * CLI worker to prune mxHeadless API audit log rows older than retention.
 *
 * Usage: php core/components/mxheadless/bin/audit-prune.php [--days=90]
 */

define('MODX_API_MODE', true);

$days = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo "Usage: php audit-prune.php [--days=N]\n";
        echo "  --days=N  Delete rows older than N days (default: mxheadless.audit.retention_days or 90)\n";
        exit(0);
    }
    if (str_starts_with($arg, '--days=')) {
        $days = max(1, (int) substr($arg, 7));
    }
}

require_once __DIR__ . '/bootstrap-cli.php';
$root = mxheadlessResolveCliModxRoot();
$corePath = $root . '/core/';
require_once $corePath . 'config/config.inc.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new \MODX\Revolution\modX();
if (!$modx->initialize('mgr')) {
    fwrite(STDERR, "Failed to initialize MODX\n");
    exit(1);
}

$bootstrap = MODX_CORE_PATH . 'components/mxheadless/bootstrap.php';
if (!is_file($bootstrap)) {
    fwrite(STDERR, "mxHeadless bootstrap not found\n");
    exit(1);
}

$namespace = ['path' => MODX_CORE_PATH . 'components/mxheadless/'];
require_once $bootstrap;

if ($days === null) {
    $days = max(1, (int) $modx->getOption('mxheadless.audit.retention_days', null, 90));
}

$repository = new \MxHeadless\Audit\AuditLogRepository($modx);
$removed = $repository->pruneOlderThan($days);

echo "Removed {$removed} audit row(s) older than {$days} day(s)\n";
