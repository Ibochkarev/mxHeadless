<?php

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

return [
    'name' => 'mxHeadless',
    'name_lower' => 'mxheadless',
    'version' => '1.0.41',
    'release' => 'pl',
    'install' => false,
    'update' => [
        'plugins' => true,
        'settings' => false,
        'menus' => true,
    ],
    'static' => [
        'plugins' => true,
    ],
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() === 'cli' ? 'ECHO' : 'HTML',
    'download' => !empty($_REQUEST['download']),
];
