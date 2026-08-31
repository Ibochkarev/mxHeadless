<?php

declare(strict_types=1);

define('MODX_API_MODE', true);

$modxIndex = mxheadlessResolveModxIndex();

if ($modxIndex === null) {
    http_response_code(500);
    header('Content-Type: application/problem+json; charset=utf-8');
    echo json_encode([
        'type' => 'https://mxheadless.dev/problems/bootstrap',
        'title' => 'MODX bootstrap failed',
        'status' => 500,
        'detail' => 'Could not locate MODX index.php.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $modxIndex;

/** @var \MODX\Revolution\modX $modx */
$modx->getRequest();
$contextKey = trim((string) $modx->getOption('mxheadless_context', null, 'web'));
if ($contextKey === '' || strcasecmp($contextKey, 'mgr') === 0) {
    $contextKey = 'web';
}
$modx->initialize($contextKey);

if (!$modx->services->has('mxheadless')) {
    http_response_code(503);
    header('Content-Type: application/problem+json; charset=utf-8');
    echo json_encode([
        'type' => 'https://mxheadless.dev/problems/service-unavailable',
        'title' => 'Service unavailable',
        'status' => 503,
        'detail' => 'mxHeadless service is not registered.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** @var \MxHeadless\Application $application */
$application = $modx->services->get('mxheadless');
$application->handleFromGlobals();

/**
 * @return non-empty-string|null
 */
function mxheadlessResolveModxIndex(): ?string
{
    $candidates = [];

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (is_string($docRoot) && $docRoot !== '') {
        $candidates[] = rtrim(str_replace('\\', '/', $docRoot), '/') . '/index.php';
    }

    $script = $_SERVER['SCRIPT_FILENAME'] ?? '';
    if (is_string($script) && $script !== '') {
        $candidates[] = dirname(str_replace('\\', '/', $script), 3) . '/index.php';
    }

    $dir = str_replace('\\', '/', __DIR__);
    for ($i = 3; $i <= 6; ++$i) {
        $candidates[] = dirname($dir, $i) . '/index.php';
    }

    foreach ($candidates as $index) {
        if (!is_string($index) || $index === '') {
            continue;
        }

        $index = str_replace('\\', '/', $index);
        $root = dirname($index);

        if (is_file($index) && is_file($root . '/core/config/config.inc.php')) {
            return $index;
        }
    }

    return null;
}
