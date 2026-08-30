<?php

/**
 * mxHeadless HTTP gateway.
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

if ($modx->event->name !== 'OnHandleRequest') {
    return;
}

if ($modx->context->get('key') === 'mgr') {
    return;
}

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);

if (!is_string($path) || $path === '') {
    $path = '/';
}

if (!$modx->services->has('mxheadless')) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[mxHeadless] Service "mxheadless" is not registered.');

    return;
}

/** @var \MxHeadless\Application $application */
$application = $modx->services->get('mxheadless');
if (!$application->matchesPath($path)) {
    return;
}

$application->handleFromGlobals();
exit;
