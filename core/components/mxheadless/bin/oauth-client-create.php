<?php

declare(strict_types=1);

/**
 * Create an mxHeadless OAuth client (secret shown once).
 *
 * Usage:
 *   php core/components/mxheadless/bin/oauth-client-create.php \
 *     --client-id=next-preview \
 *     --name="Next preview" \
 *     --scopes="resources.read,contexts.read" \
 *     [--grants=client_credentials] \
 *     [--rate-limit-max=30] \
 *     [--rate-limit-window=60]
 */

define('MODX_API_MODE', true);

$clientId = '';
$name = '';
$scopes = '';
$grants = 'client_credentials';
$rateLimitMax = null;
$rateLimitWindow = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--client-id=')) {
        $clientId = trim(substr($arg, 12));
    } elseif (str_starts_with($arg, '--name=')) {
        $name = trim(substr($arg, 7));
    } elseif (str_starts_with($arg, '--scopes=')) {
        $scopes = trim(substr($arg, 9));
    } elseif (str_starts_with($arg, '--grants=')) {
        $grants = trim(substr($arg, 9));
    } elseif (str_starts_with($arg, '--rate-limit-max=')) {
        $rateLimitMax = max(1, (int) substr($arg, 17));
    } elseif (str_starts_with($arg, '--rate-limit-window=')) {
        $rateLimitWindow = max(1, (int) substr($arg, 20));
    }
}

if ($clientId === '') {
    fwrite(STDERR, "Missing required --client-id=\n");
    exit(1);
}

if ($name === '') {
    $name = $clientId;
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

$scopeList = array_values(array_filter(array_map('trim', explode(',', $scopes))));
$grantList = array_values(array_filter(array_map('trim', explode(',', $grants))));
if ($grantList === []) {
    $grantList = ['client_credentials'];
}

$secret = bin2hex(random_bytes(16));

/** @var \MxHeadless\Model\modMxHeadlessOAuthClient|null $existing */
$existing = $modx->getObject(\MxHeadless\Model\modMxHeadlessOAuthClient::class, ['client_id' => $clientId]);
if ($existing !== null) {
    fwrite(STDERR, "OAuth client already exists: {$clientId}\n");
    exit(1);
}

/** @var \MxHeadless\Model\modMxHeadlessOAuthClient|null $model */
$model = $modx->newObject(\MxHeadless\Model\modMxHeadlessOAuthClient::class);
if ($model === null) {
    fwrite(STDERR, "Failed to create OAuth client model\n");
    exit(1);
}

$data = [
    'client_id' => $clientId,
    'client_secret_hash' => password_hash($secret, PASSWORD_DEFAULT),
    'name' => $name,
    'scopes' => $scopeList,
    'grant_types' => $grantList,
    'revoked' => false,
    'created_on' => time(),
];
if ($rateLimitMax !== null) {
    $data['rate_limit_max'] = $rateLimitMax;
}
if ($rateLimitWindow !== null) {
    $data['rate_limit_window'] = $rateLimitWindow;
}

$model->fromArray($data);
if (!$model->save()) {
    fwrite(STDERR, "Failed to save OAuth client\n");
    exit(1);
}

echo "OAuth client created\n";
echo "client_id: {$clientId}\n";
echo "client_secret: {$secret}\n";
echo "grant_types: " . implode(',', $grantList) . "\n";
echo "scopes: " . ($scopeList === [] ? '(none — token gets *)' : implode(',', $scopeList)) . "\n";
echo "\nCopy the secret now. It is not stored in plain text.\n";
