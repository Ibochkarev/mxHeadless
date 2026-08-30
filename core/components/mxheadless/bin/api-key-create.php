<?php

declare(strict_types=1);

/**
 * Create an mxHeadless API key (secret shown once).
 *
 * Usage:
 *   php core/components/mxheadless/bin/api-key-create.php \
 *     --name="CI build" \
 *     --scopes="resources.read,contexts.read" \
 *     [--rate-limit-max=60] \
 *     [--rate-limit-window=60]
 */

define('MODX_API_MODE', true);

$name = '';
$scopes = '';
$rateLimitMax = null;
$rateLimitWindow = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--name=')) {
        $name = trim(substr($arg, 7));
    } elseif (str_starts_with($arg, '--scopes=')) {
        $scopes = trim(substr($arg, 9));
    } elseif (str_starts_with($arg, '--rate-limit-max=')) {
        $rateLimitMax = max(1, (int) substr($arg, 17));
    } elseif (str_starts_with($arg, '--rate-limit-window=')) {
        $rateLimitWindow = max(1, (int) substr($arg, 20));
    }
}

if ($name === '') {
    fwrite(STDERR, "Missing required --name=\n");
    exit(1);
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

$scopeList = array_values(array_filter(array_map('trim', explode(',', $scopes))));
$lookupId = bin2hex(random_bytes(8));
$secret = bin2hex(random_bytes(16));
$plain = 'mxh_' . $lookupId . '_' . $secret;

/** @var \MxHeadless\Model\modMxHeadlessApiKey|null $model */
$model = $modx->newObject(\MxHeadless\Model\modMxHeadlessApiKey::class);
if ($model === null) {
    fwrite(STDERR, "Failed to create API key model\n");
    exit(1);
}

$data = [
    'lookup_id' => $lookupId,
    'secret_hash' => password_hash($secret, PASSWORD_DEFAULT),
    'name' => $name,
    'scopes' => $scopeList !== [] ? $scopeList : ['*'],
    'revoked' => false,
    'created_on' => time(),
    'created_by' => 0,
];
if ($rateLimitMax !== null) {
    $data['rate_limit_max'] = $rateLimitMax;
}
if ($rateLimitWindow !== null) {
    $data['rate_limit_window'] = $rateLimitWindow;
}

$model->fromArray($data);
if (!$model->save()) {
    fwrite(STDERR, "Failed to save API key\n");
    exit(1);
}

echo "API key created\n";
echo "name: {$name}\n";
echo "token: {$plain}\n";
echo "scopes: " . implode(',', $data['scopes']) . "\n";
echo "\nCopy the token now. It is not stored in plain text.\n";
