<?php

/**
 * Resolver: anonymous install/upgrade metrics.
 *
 * Collects depersonalized environment data for compatibility insights.
 * No personal data, domains, IP addresses, or secrets.
 *
 * @package mxheadless
 */

use xPDO\Transport\xPDOTransport;

/** @var xPDOTransport $transport */
/** @var array<string, mixed> $options */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return true;
}

$modx = $transport->xpdo;
/** @var \modX $modx */

if (!in_array($options[xPDOTransport::PACKAGE_ACTION] ?? null, [
    xPDOTransport::ACTION_INSTALL,
    xPDOTransport::ACTION_UPGRADE,
], true)) {
    return true;
}

$collectMetrics = static function () use ($modx, $transport): array {
    $installType = 'fresh';
    $previousVersion = null;

    $existingPackage = $modx->getObject('transport.modTransportPackage', [
        'package_name' => 'mxheadless',
        'installed:IS NOT' => null,
    ]);

    if ($existingPackage) {
        $installType = 'upgrade';
        $previousVersion = $existingPackage->get('version_major') . '.'
            . $existingPackage->get('version_minor') . '.'
            . $existingPackage->get('version_patch');
        $release = $existingPackage->get('release');
        if ($release) {
            $previousVersion .= '-' . $release;
            $releaseIndex = $existingPackage->get('release_index');
            if ($releaseIndex) {
                $previousVersion .= $releaseIndex;
            }
        }
    }

    $modx->getVersionData();
    $modxVersion = $modx->version['full_version'] ?? 'unknown';

    $pdoToolsVersion = null;
    $pdoPackage = $modx->getObject('transport.modTransportPackage', [
        'package_name' => 'pdoTools',
        'installed:IS NOT' => null,
    ]);
    if ($pdoPackage) {
        $pdoToolsVersion = $pdoPackage->get('version_major') . '.'
            . $pdoPackage->get('version_minor') . '.'
            . $pdoPackage->get('version_patch');
        $release = $pdoPackage->get('release');
        if ($release) {
            $pdoToolsVersion .= '-' . $release;
        }
    }

    $minishop3Version = null;
    foreach (['minishop3', 'MiniShop3'] as $pkgName) {
        $ms3Package = $modx->getObject('transport.modTransportPackage', [
            'package_name' => $pkgName,
            'installed:IS NOT' => null,
        ]);
        if ($ms3Package) {
            $minishop3Version = $ms3Package->get('version_major') . '.'
                . $ms3Package->get('version_minor') . '.'
                . $ms3Package->get('version_patch');
            break;
        }
    }

    $contextCount = $modx->getCount('modContext');
    $modxLocale = $modx->getOption('cultureKey', null, 'en');

    $dbVersion = null;
    $dbType = null;
    try {
        $pdo = $modx->getConnection()->pdo ?? null;
        if ($pdo instanceof PDO) {
            $dbType = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $dbVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        }
    } catch (Throwable $e) {
    }

    $webServer = null;
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
    if (stripos($serverSoftware, 'apache') !== false) {
        $webServer = 'Apache';
    } elseif (stripos($serverSoftware, 'nginx') !== false) {
        $webServer = 'Nginx';
    } elseif (stripos($serverSoftware, 'litespeed') !== false) {
        $webServer = 'LiteSpeed';
    } elseif ($serverSoftware !== '') {
        $webServer = explode('/', $serverSoftware)[0];
    }

    $packageVersion = 'unknown';
    if (!empty($transport->signature)) {
        $parts = explode('-', (string) $transport->signature, 2);
        if (isset($parts[1])) {
            $packageVersion = $parts[1];
        }
    }

    return [
        'package_name' => 'mxheadless',
        'package_version' => $packageVersion,
        'install_type' => $installType,
        'previous_version' => $previousVersion,
        'php_version' => PHP_VERSION,
        'php_sapi' => PHP_SAPI,
        'modx_version' => $modxVersion,
        'modx_locale' => $modxLocale,
        'modx_context_count' => $contextCount,
        'pdo_tools_version' => $pdoToolsVersion,
        'minishop3_version' => $minishop3Version,
        'db_type' => $dbType,
        'db_version' => $dbVersion,
        'os_type' => PHP_OS_FAMILY,
        'web_server' => $webServer,
    ];
};

$sendMetrics = static function (array $metrics): bool {
    $url = 'https://metrics.modx.pro/';
    $payload = json_encode($metrics, JSON_UNESCAPED_UNICODE);
    if (!is_string($payload)) {
        return false;
    }

    $timeout = 2;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $success = curl_errno($ch) === 0;
        curl_close($ch);

        return $success;
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true],
        ]);
        @file_get_contents($url, false, $context);

        return true;
    }

    return false;
};

$t0 = microtime(true);
$modx->log(\modX::LOG_LEVEL_INFO, '== SendMetrics: migrating');

try {
    $metrics = $collectMetrics();
    $sendMetrics($metrics);
} catch (Throwable $e) {
}

$elapsed = sprintf('%.4f', microtime(true) - $t0);
$modx->log(\modX::LOG_LEVEL_INFO, '== SendMetrics: migrated ' . $elapsed . 's');
$modx->log(\modX::LOG_LEVEL_INFO, '[mxHeadless] Installation completed');

return true;
