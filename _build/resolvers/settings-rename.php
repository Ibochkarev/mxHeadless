<?php

/** @var xPDO\Transport\xPDOTransport $transport */
/** @var array $options */
/** @var MODX\Revolution\modX $modx */

if (!$transport->xpdo) {
    return true;
}

$modx = $transport->xpdo;

/**
 * Copy values from dotted setting keys (pre-1.0.42) to underscore keys, then remove the old rows.
 */
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $map = [
            'mxheadless.api.prefix' => 'mxheadless_api_prefix',
            'mxheadless.debug' => 'mxheadless_debug',
            'mxheadless.enabled' => 'mxheadless_enabled',
            'mxheadless.cache.enabled' => 'mxheadless_cache_enabled',
            'mxheadless.cache_ttl' => 'mxheadless_cache_ttl',
            'mxheadless.rate_limit.enabled' => 'mxheadless_rate_limit_enabled',
            'mxheadless.rate_limit.max_requests' => 'mxheadless_rate_limit_max_requests',
            'mxheadless.rate_limit.window_seconds' => 'mxheadless_rate_limit_window_seconds',
            'mxheadless.cors.enabled' => 'mxheadless_cors_enabled',
            'mxheadless.cors.allowed_origins' => 'mxheadless_cors_allowed_origins',
            'mxheadless.cors.allowed_methods' => 'mxheadless_cors_allowed_methods',
            'mxheadless.cors.allowed_headers' => 'mxheadless_cors_allowed_headers',
            'mxheadless.cors.expose_headers' => 'mxheadless_cors_expose_headers',
            'mxheadless.cors.allow_credentials' => 'mxheadless_cors_allow_credentials',
            'mxheadless.max_body_bytes' => 'mxheadless_max_body_bytes',
            'mxheadless.max_uri_bytes' => 'mxheadless_max_uri_bytes',
            'mxheadless.trusted_proxies' => 'mxheadless_trusted_proxies',
            'mxheadless.csrf.enabled' => 'mxheadless_csrf_enabled',
            'mxheadless.idempotency.enabled' => 'mxheadless_idempotency_enabled',
            'mxheadless.idempotency_ttl' => 'mxheadless_idempotency_ttl',
            'mxheadless.webhook.max_attempts' => 'mxheadless_webhook_max_attempts',
            'mxheadless.webhook.worker_limit' => 'mxheadless_webhook_worker_limit',
            'mxheadless.webhook.allow_private_urls' => 'mxheadless_webhook_allow_private_urls',
            'mxheadless.audit.enabled' => 'mxheadless_audit_enabled',
            'mxheadless.audit.retention_days' => 'mxheadless_audit_retention_days',
            'mxheadless.audit.log_get' => 'mxheadless_audit_log_get',
            'mxheadless.oauth.enabled' => 'mxheadless_oauth_enabled',
            'mxheadless.oauth.token_ttl' => 'mxheadless_oauth_token_ttl',
            'mxheadless.oauth.password_grant_enabled' => 'mxheadless_oauth_password_grant_enabled',
            'mxheadless.swagger.enabled' => 'mxheadless_swagger_enabled',
        ];

        $renamed = 0;
        foreach ($map as $oldKey => $newKey) {
            /** @var MODX\Revolution\modSystemSetting|null $old */
            $old = $modx->getObject(MODX\Revolution\modSystemSetting::class, ['key' => $oldKey]);
            if ($old === null) {
                continue;
            }

            /** @var MODX\Revolution\modSystemSetting|null $new */
            $new = $modx->getObject(MODX\Revolution\modSystemSetting::class, ['key' => $newKey]);
            if ($new === null) {
                $new = $modx->newObject(MODX\Revolution\modSystemSetting::class);
                $new->fromArray([
                    'key' => $newKey,
                    'namespace' => 'mxheadless',
                    'xtype' => $old->get('xtype'),
                    'area' => $old->get('area'),
                    'value' => $old->get('value'),
                ], '', true, true);
            } else {
                $new->set('value', $old->get('value'));
            }

            if ($new->save()) {
                $old->remove();
                ++$renamed;
            }
        }

        if ($renamed > 0) {
            $modx->log(
                MODX\Revolution\modX::LOG_LEVEL_INFO,
                '[mxHeadless] Renamed ' . $renamed . ' system setting key(s) from dotted to underscore form.',
            );
            $modx->reloadConfig();
        }
        break;
}

return true;
