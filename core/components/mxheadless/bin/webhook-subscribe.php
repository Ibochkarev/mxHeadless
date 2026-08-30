<?php

declare(strict_types=1);

/**
 * Create an mxHeadless webhook subscription.
 *
 * Usage:
 *   php core/components/mxheadless/bin/webhook-subscribe.php \
 *     --name="ISR" \
 *     --url="https://frontend.example/api/revalidate" \
 *     [--events="resources.created,resources.updated,resources.deleted"] \
 *     [--secret="shared-hmac-secret"] \
 *     [--inactive]
 */

define('MODX_API_MODE', true);

$name = '';
$url = '';
$events = 'resources.created,resources.updated,resources.deleted';
$secret = '';
$active = true;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--name=')) {
        $name = trim(substr($arg, 7));
    } elseif (str_starts_with($arg, '--url=')) {
        $url = trim(substr($arg, 6));
    } elseif (str_starts_with($arg, '--events=')) {
        $events = trim(substr($arg, 9));
    } elseif (str_starts_with($arg, '--secret=')) {
        $secret = trim(substr($arg, 9));
    } elseif ($arg === '--inactive') {
        $active = false;
    }
}

if ($name === '' || $url === '') {
    fwrite(STDERR, "Required: --name= and --url=\n");
    exit(1);
}

if (!preg_match('#^https?://#i', $url)) {
    fwrite(STDERR, "URL must start with http:// or https://\n");
    exit(1);
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

$eventList = array_values(array_filter(array_map('trim', explode(',', $events))));
if ($eventList === []) {
    $eventList = ['*'];
}

if ($secret === '') {
    $secret = bin2hex(random_bytes(16));
}

/** @var \MxHeadless\Model\modMxHeadlessWebhookSubscription|null $model */
$model = $modx->newObject(\MxHeadless\Model\modMxHeadlessWebhookSubscription::class);
if ($model === null) {
    fwrite(STDERR, "Failed to create subscription model\n");
    exit(1);
}

$model->fromArray([
    'name' => $name,
    'url' => $url,
    'secret' => $secret,
    'events' => $eventList,
    'active' => $active,
    'created_on' => time(),
]);

if (!$model->save()) {
    fwrite(STDERR, "Failed to save subscription\n");
    exit(1);
}

echo "Webhook subscription created\n";
echo "id: " . $model->get('id') . "\n";
echo "name: {$name}\n";
echo "url: {$url}\n";
echo "events: " . implode(',', $eventList) . "\n";
echo "secret: {$secret}\n";
echo "active: " . ($active ? '1' : '0') . "\n";
echo "\nStore the secret for signature verification (X-MxHeadless-Signature).\n";
