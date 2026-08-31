<?php

/**
 * System setting labels (English)
 *
 * @package mxheadless
 */

$_lang['setting_mxheadless_api_prefix'] = 'API path prefix';
$_lang['setting_mxheadless_api_prefix_desc'] = 'Public URL prefix before /v1 (default /api).';
$_lang['setting_mxheadless_context'] = 'API context';
$_lang['setting_mxheadless_context_desc'] = 'MODX context key used to initialize API requests (default web). The mgr value is ignored and falls back to web.';

$_lang['setting_mxheadless_enabled'] = 'API enabled';
$_lang['setting_mxheadless_enabled_desc'] = 'Kill switch. When No, gateway returns 503 service_disabled (health/discovery may stay up).';

$_lang['setting_mxheadless_debug'] = 'Debug mode';
$_lang['setting_mxheadless_debug_desc'] = 'Include exception details in 500 problem+json responses.';

$_lang['setting_mxheadless_cache_enabled'] = 'HTTP response cache';
$_lang['setting_mxheadless_cache_enabled_desc'] = 'Cache anonymous GET/HEAD responses with ETag.';

$_lang['setting_mxheadless_cache_ttl'] = 'Cache TTL (seconds)';
$_lang['setting_mxheadless_cache_ttl_desc'] = 'Max-age for public cached responses.';

$_lang['setting_mxheadless_rate_limit_enabled'] = 'Rate limiting';
$_lang['setting_mxheadless_rate_limit_enabled_desc'] = 'Enforce request budgets per identity/IP.';

$_lang['setting_mxheadless_rate_limit_max_requests'] = 'Default max requests';
$_lang['setting_mxheadless_rate_limit_max_requests_desc'] = 'Requests allowed per window when no per-key override is set.';

$_lang['setting_mxheadless_rate_limit_window_seconds'] = 'Rate limit window (seconds)';
$_lang['setting_mxheadless_rate_limit_window_seconds_desc'] = 'Sliding/fixed window length for the default budget.';

$_lang['setting_mxheadless_cors_enabled'] = 'CORS enabled';
$_lang['setting_mxheadless_cors_enabled_desc'] = 'Required for browser Nuxt/Next on another origin.';

$_lang['setting_mxheadless_cors_allowed_origins'] = 'CORS allowed origins';
$_lang['setting_mxheadless_cors_allowed_origins_desc'] = 'Comma-separated exact origins, or *.';

$_lang['setting_mxheadless_cors_allowed_methods'] = 'CORS allowed methods';
$_lang['setting_mxheadless_cors_allowed_methods_desc'] = 'Comma-separated HTTP methods for preflight.';

$_lang['setting_mxheadless_cors_allowed_headers'] = 'CORS allowed headers';
$_lang['setting_mxheadless_cors_allowed_headers_desc'] = 'Comma-separated request headers clients may send.';

$_lang['setting_mxheadless_cors_expose_headers'] = 'CORS expose headers';
$_lang['setting_mxheadless_cors_expose_headers_desc'] = 'Response headers browser JS may read (include ETag).';

$_lang['setting_mxheadless_cors_allow_credentials'] = 'CORS allow credentials';
$_lang['setting_mxheadless_cors_allow_credentials_desc'] = 'Do not combine with * origins.';

$_lang['setting_mxheadless_max_body_bytes'] = 'Max body size (bytes)';
$_lang['setting_mxheadless_max_body_bytes_desc'] = 'Reject larger request bodies.';

$_lang['setting_mxheadless_max_uri_bytes'] = 'Max URI bytes';
$_lang['setting_mxheadless_max_uri_bytes_desc'] = 'Reject longer page URI path parameters.';

$_lang['setting_mxheadless_trusted_proxies'] = 'Trusted proxies';
$_lang['setting_mxheadless_trusted_proxies_desc'] = 'Comma-separated IPs/CIDRs trusted for X-Forwarded-* / client IP.';

$_lang['setting_mxheadless_idempotency_enabled'] = 'Idempotency';
$_lang['setting_mxheadless_idempotency_enabled_desc'] = 'Honor Idempotency-Key on POST (cache 2xx).';

$_lang['setting_mxheadless_idempotency_ttl'] = 'Idempotency TTL (seconds)';
$_lang['setting_mxheadless_idempotency_ttl_desc'] = 'How long to remember Idempotency-Key results.';

$_lang['setting_mxheadless_webhook_max_attempts'] = 'Webhook max attempts';
$_lang['setting_mxheadless_webhook_max_attempts_desc'] = 'Delivery retries before giving up.';

$_lang['setting_mxheadless_webhook_worker_limit'] = 'Webhook worker batch size';
$_lang['setting_mxheadless_webhook_worker_limit_desc'] = 'Max deliveries processed per worker run.';

$_lang['setting_mxheadless_webhook_allow_private_urls'] = 'Allow private webhook URLs';
$_lang['setting_mxheadless_webhook_allow_private_urls_desc'] = 'Dev only: allow localhost/private IPs and skip TLS verify.';

$_lang['setting_mxheadless_audit_enabled'] = 'Audit log';
$_lang['setting_mxheadless_audit_enabled_desc'] = 'Write API access rows to mxheadless_api_log.';

$_lang['setting_mxheadless_audit_retention_days'] = 'Audit retention (days)';
$_lang['setting_mxheadless_audit_retention_days_desc'] = 'Used by audit-prune CLI.';

$_lang['setting_mxheadless_audit_log_get'] = 'Audit GET requests';
$_lang['setting_mxheadless_audit_log_get_desc'] = 'When Yes, also log safe GETs (noisier).';

$_lang['setting_mxheadless_oauth_enabled'] = 'OAuth token endpoint';
$_lang['setting_mxheadless_oauth_enabled_desc'] = 'Enable POST /auth/token for mxt_* tokens.';

$_lang['setting_mxheadless_oauth_token_ttl'] = 'OAuth token TTL (seconds)';
$_lang['setting_mxheadless_oauth_token_ttl_desc'] = 'Default lifetime for issued opaque tokens.';

$_lang['setting_mxheadless_oauth_password_grant_enabled'] = 'Password grant';
$_lang['setting_mxheadless_oauth_password_grant_enabled_desc'] = 'Allow resource-owner password credentials (usually leave No).';

$_lang['setting_mxheadless_swagger_enabled'] = 'Swagger UI';
$_lang['setting_mxheadless_swagger_enabled_desc'] = 'Serve interactive docs at GET /api/v1/docs. Raw OpenAPI JSON stays available when disabled.';

$_lang['setting_mxheadless_csrf_enabled'] = 'CSRF for session mutations';
$_lang['setting_mxheadless_csrf_enabled_desc'] = 'Require X-CSRF-Token on POST/PUT/PATCH/DELETE when the caller uses a Manager session.';
