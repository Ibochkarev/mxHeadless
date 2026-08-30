<?php

/**
 * mxHeadless mgr connector.
 */

if (php_sapi_name() !== 'cli') {
    @ini_set('display_errors', '0');
}

try {
    $configCore = dirname(__DIR__, 3) . '/config.core.php';
    if (!is_file($configCore)) {
        throw new RuntimeException('config.core.php not found');
    }

    require_once $configCore;
    require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
    require_once MODX_CONNECTORS_PATH . 'index.php';

    $corePath = $modx->getOption(
        'mxheadless.core_path',
        null,
        $modx->getOption('core_path') . 'components/mxheadless/',
    );

    $namespace = ['path' => $corePath];
    $bootstrap = $corePath . 'bootstrap.php';
    if (is_file($bootstrap)) {
        require_once $bootstrap;
    }

    $modx->lexicon->load('mxheadless:default');
    $modx->getRequest();

    $modx->request->handleRequest([
        'processors_path' => $corePath . 'processors/',
        'location' => '',
    ]);
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
