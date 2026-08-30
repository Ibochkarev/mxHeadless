<?php

declare(strict_types=1);

/**
 * CLI worker for mxHeadless webhook outbox retries.
 *
 * Usage: php core/components/mxheadless/bin/webhook-worker.php [--limit=50]
 */

define('MODX_API_MODE', true);

$limit = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$root = dirname(__DIR__, 4);
$corePath = $root . '/core/';
if (!is_file($corePath . 'config/config.inc.php')) {
    fwrite(STDERR, "MODX core not found at {$corePath}\n");
    exit(1);
}

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

/** @var \MxHeadless\Application $app */
$app = $modx->services->get('mxheadless');
$app->bootstrap();

if ($limit === null) {
    $limit = max(1, (int) $modx->getOption('mxheadless.webhook.worker_limit', null, 50));
}

$processed = $app->webhookDispatcher()->processPending($limit);

echo "Processed {$processed} webhook(s)\n";
