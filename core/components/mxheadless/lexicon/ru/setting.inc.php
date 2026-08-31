<?php

/**
 * Подписи системных настроек (русский)
 *
 * @package mxheadless
 */

$_lang['setting_mxheadless_api_prefix'] = 'Префикс API';
$_lang['setting_mxheadless_api_prefix_desc'] = 'Публичный префикс до /v1 (по умолчанию /api).';
$_lang['setting_mxheadless_context'] = 'Контекст API';
$_lang['setting_mxheadless_context_desc'] = 'Контекст инициализации MODX для API (по умолчанию web). Значение mgr игнорируется в пользу web.';

$_lang['setting_mxheadless_enabled'] = 'API включён';
$_lang['setting_mxheadless_enabled_desc'] = 'Kill switch. При «Нет» gateway отвечает 503 service_disabled.';

$_lang['setting_mxheadless_debug'] = 'Режим отладки';
$_lang['setting_mxheadless_debug_desc'] = 'Добавлять детали исключения в 500 problem+json.';

$_lang['setting_mxheadless_cache_enabled'] = 'HTTP-кэш ответов';
$_lang['setting_mxheadless_cache_enabled_desc'] = 'Кэшировать anonymous GET/HEAD с ETag.';

$_lang['setting_mxheadless_cache_ttl'] = 'TTL кэша (сек)';
$_lang['setting_mxheadless_cache_ttl_desc'] = 'max-age для публичных кэшированных ответов.';

$_lang['setting_mxheadless_rate_limit_enabled'] = 'Rate limiting';
$_lang['setting_mxheadless_rate_limit_enabled_desc'] = 'Лимиты запросов по identity/IP.';

$_lang['setting_mxheadless_rate_limit_max_requests'] = 'Лимит запросов по умолчанию';
$_lang['setting_mxheadless_rate_limit_max_requests_desc'] = 'Сколько запросов в окне, если у ключа нет своего лимита.';

$_lang['setting_mxheadless_rate_limit_window_seconds'] = 'Окно rate limit (сек)';
$_lang['setting_mxheadless_rate_limit_window_seconds_desc'] = 'Длина окна для лимита по умолчанию.';

$_lang['setting_mxheadless_cors_enabled'] = 'CORS включён';
$_lang['setting_mxheadless_cors_enabled_desc'] = 'Нужен для браузерного Nuxt/Next с другого origin.';

$_lang['setting_mxheadless_cors_allowed_origins'] = 'Разрешённые origins';
$_lang['setting_mxheadless_cors_allowed_origins_desc'] = 'Через запятую точные origins или *.';

$_lang['setting_mxheadless_cors_allowed_methods'] = 'Разрешённые методы';
$_lang['setting_mxheadless_cors_allowed_methods_desc'] = 'HTTP-методы для preflight через запятую.';

$_lang['setting_mxheadless_cors_allowed_headers'] = 'Разрешённые заголовки';
$_lang['setting_mxheadless_cors_allowed_headers_desc'] = 'Заголовки запроса, которые может слать клиент.';

$_lang['setting_mxheadless_cors_expose_headers'] = 'Expose-Headers';
$_lang['setting_mxheadless_cors_expose_headers_desc'] = 'Заголовки ответа, доступные JS (включая ETag).';

$_lang['setting_mxheadless_cors_allow_credentials'] = 'CORS credentials';
$_lang['setting_mxheadless_cors_allow_credentials_desc'] = 'Не сочетать с origins = *.';

$_lang['setting_mxheadless_max_body_bytes'] = 'Макс. размер тела (байт)';
$_lang['setting_mxheadless_max_body_bytes_desc'] = 'Отклонять более крупные тела запросов.';

$_lang['setting_mxheadless_max_uri_bytes'] = 'Макс. длина URI (байт)';
$_lang['setting_mxheadless_max_uri_bytes_desc'] = 'Лимит для path-параметра страницы.';

$_lang['setting_mxheadless_trusted_proxies'] = 'Доверенные прокси';
$_lang['setting_mxheadless_trusted_proxies_desc'] = 'IP/CIDR через запятую для X-Forwarded-* / IP клиента.';

$_lang['setting_mxheadless_idempotency_enabled'] = 'Идемпотентность';
$_lang['setting_mxheadless_idempotency_enabled_desc'] = 'Учитывать Idempotency-Key на POST (кэш 2xx).';

$_lang['setting_mxheadless_idempotency_ttl'] = 'TTL идемпотентности (сек)';
$_lang['setting_mxheadless_idempotency_ttl_desc'] = 'Сколько хранить результат по Idempotency-Key.';

$_lang['setting_mxheadless_webhook_max_attempts'] = 'Webhook: макс. попыток';
$_lang['setting_mxheadless_webhook_max_attempts_desc'] = 'Повторы доставки до отказа.';

$_lang['setting_mxheadless_webhook_worker_limit'] = 'Webhook: размер батча';
$_lang['setting_mxheadless_webhook_worker_limit_desc'] = 'Сколько доставок за один запуск worker.';

$_lang['setting_mxheadless_webhook_allow_private_urls'] = 'Webhook: private URL';
$_lang['setting_mxheadless_webhook_allow_private_urls_desc'] = 'Только для dev: localhost/private IP и отключение TLS verify.';

$_lang['setting_mxheadless_audit_enabled'] = 'Журнал аудита';
$_lang['setting_mxheadless_audit_enabled_desc'] = 'Писать обращения в mxheadless_api_log.';

$_lang['setting_mxheadless_audit_retention_days'] = 'Хранение аудита (дней)';
$_lang['setting_mxheadless_audit_retention_days_desc'] = 'Для CLI audit-prune.';

$_lang['setting_mxheadless_audit_log_get'] = 'Логировать GET';
$_lang['setting_mxheadless_audit_log_get_desc'] = 'При «Да» писать и безопасные GET (шумнее).';

$_lang['setting_mxheadless_oauth_enabled'] = 'OAuth token endpoint';
$_lang['setting_mxheadless_oauth_enabled_desc'] = 'Включить POST /auth/token для токенов mxt_*.';

$_lang['setting_mxheadless_oauth_token_ttl'] = 'TTL OAuth-токена (сек)';
$_lang['setting_mxheadless_oauth_token_ttl_desc'] = 'Время жизни выдаваемых opaque-токенов.';

$_lang['setting_mxheadless_oauth_password_grant_enabled'] = 'Password grant';
$_lang['setting_mxheadless_oauth_password_grant_enabled_desc'] = 'Разрешить password credentials (обычно оставьте «Нет»).';

$_lang['setting_mxheadless_swagger_enabled'] = 'Swagger UI';
$_lang['setting_mxheadless_swagger_enabled_desc'] = 'Интерактивная документация на GET /api/v1/docs. Сырой OpenAPI JSON остаётся доступен при отключении.';

$_lang['setting_mxheadless_csrf_enabled'] = 'CSRF для session-мутаций';
$_lang['setting_mxheadless_csrf_enabled_desc'] = 'Требовать X-CSRF-Token на POST/PUT/PATCH/DELETE, если вызывающий ходит с Manager session.';
