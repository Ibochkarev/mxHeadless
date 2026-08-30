<?php

/** @var xPDO\Transport\xPDOTransport $transport */
/** @var array $options */
/** @var MODX\Revolution\modX $modx */

if (!$transport->xpdo) {
    return true;
}

$modx = $transport->xpdo;

if (!function_exists('mxheadlessEnsureColumn')) {
    /**
     * @param MODX\Revolution\modX $modx
     */
    function mxheadlessEnsureColumn(modX $modx, string $table, string $column, string $definition): void
    {
        $stmt = $modx->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if ($stmt !== false && $stmt->fetch()) {
            return;
        }

        $modx->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $prefix = (string) $modx->getOption('table_prefix', null, 'modx_');
        $apiKeysTable = $prefix . 'mxheadless_api_keys';
        $subscriptionsTable = $prefix . 'mxheadless_webhook_subscriptions';
        $deliveriesTable = $prefix . 'mxheadless_webhook_deliveries';
        $auditLogTable = $prefix . 'mxheadless_api_log';
        $oauthClientsTable = $prefix . 'mxheadless_oauth_clients';
        $oauthTokensTable = $prefix . 'mxheadless_oauth_tokens';

        $modx->exec("CREATE TABLE IF NOT EXISTS `{$apiKeysTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `lookup_id` varchar(32) NOT NULL,
            `secret_hash` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL DEFAULT '',
            `scopes` text,
            `expires_on` int(10) unsigned DEFAULT NULL,
            `revoked` tinyint(1) unsigned NOT NULL DEFAULT 0,
            `created_on` int(10) unsigned NOT NULL DEFAULT 0,
            `created_by` int(10) unsigned NOT NULL DEFAULT 0,
            `last_used_on` int(10) unsigned DEFAULT NULL,
            `rate_limit_max` int(10) unsigned DEFAULT NULL,
            `rate_limit_window` int(10) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `lookup_id` (`lookup_id`),
            KEY `revoked` (`revoked`),
            KEY `expires_on` (`expires_on`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mxheadlessEnsureColumn($modx, $apiKeysTable, 'rate_limit_max', 'int(10) unsigned DEFAULT NULL');
        mxheadlessEnsureColumn($modx, $apiKeysTable, 'rate_limit_window', 'int(10) unsigned DEFAULT NULL');

        $modx->exec("CREATE TABLE IF NOT EXISTS `{$subscriptionsTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `url` varchar(2048) NOT NULL,
            `secret` varchar(255) NOT NULL DEFAULT '',
            `events` text,
            `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
            `created_on` int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `active` (`active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $modx->exec("CREATE TABLE IF NOT EXISTS `{$deliveriesTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `subscription_id` int(10) unsigned NOT NULL DEFAULT 0,
            `event` varchar(128) NOT NULL,
            `payload` longtext NOT NULL,
            `url` varchar(2048) NOT NULL,
            `secret` varchar(255) NOT NULL DEFAULT '',
            `status` varchar(32) NOT NULL DEFAULT 'pending',
            `attempts` int(10) unsigned NOT NULL DEFAULT 0,
            `last_error` text,
            `created_on` int(10) unsigned NOT NULL DEFAULT 0,
            `delivered_on` int(10) unsigned DEFAULT NULL,
            `next_attempt_on` int(10) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `status` (`status`),
            KEY `subscription_id` (`subscription_id`),
            KEY `next_attempt_on` (`next_attempt_on`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $modx->exec("CREATE TABLE IF NOT EXISTS `{$oauthClientsTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `client_id` varchar(64) NOT NULL,
            `client_secret_hash` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL DEFAULT '',
            `scopes` text,
            `grant_types` text,
            `revoked` tinyint(1) unsigned NOT NULL DEFAULT 0,
            `created_on` int(10) unsigned NOT NULL DEFAULT 0,
            `rate_limit_max` int(10) unsigned DEFAULT NULL,
            `rate_limit_window` int(10) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `client_id` (`client_id`),
            KEY `revoked` (`revoked`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $modx->exec("CREATE TABLE IF NOT EXISTS `{$oauthTokensTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `token_id` varchar(32) NOT NULL,
            `token_hash` varchar(255) NOT NULL,
            `client_id` int(10) unsigned NOT NULL DEFAULT 0,
            `user_id` int(10) unsigned DEFAULT NULL,
            `scopes` text,
            `expires_on` int(10) unsigned NOT NULL DEFAULT 0,
            `revoked` tinyint(1) unsigned NOT NULL DEFAULT 0,
            `created_on` int(10) unsigned NOT NULL DEFAULT 0,
            `last_used_on` int(10) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token_id` (`token_id`),
            KEY `client_id` (`client_id`),
            KEY `expires_on` (`expires_on`),
            KEY `revoked` (`revoked`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $modx->exec("CREATE TABLE IF NOT EXISTS `{$auditLogTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `request_id` varchar(64) NOT NULL DEFAULT '',
            `identity_key` varchar(128) NOT NULL DEFAULT '',
            `api_key_id` int(10) unsigned DEFAULT NULL,
            `method` varchar(8) NOT NULL DEFAULT '',
            `path` varchar(2048) NOT NULL DEFAULT '',
            `context_key` varchar(64) NOT NULL DEFAULT '',
            `status_code` smallint(5) unsigned NOT NULL DEFAULT 0,
            `duration_ms` int(10) unsigned NOT NULL DEFAULT 0,
            `created_on` int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `request_id` (`request_id`),
            KEY `identity_key` (`identity_key`),
            KEY `api_key_id` (`api_key_id`),
            KEY `created_on` (`created_on`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $modx->log(modX::LOG_LEVEL_INFO, '[mxHeadless] Database tables ensured.');
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return true;
