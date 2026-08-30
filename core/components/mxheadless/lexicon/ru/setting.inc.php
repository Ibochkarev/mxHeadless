<?php

/**
 * Подписи системных настроек (русский)
 *
 * @package mxheadless
 */

$_lang['setting_mxheadless.api.prefix'] = 'Префикс API';
$_lang['setting_mxheadless.api.prefix_desc'] = 'Публичный префикс до /v1 (по умолчанию /api).';

$_lang['setting_mxheadless.enabled'] = 'API включён';
$_lang['setting_mxheadless.enabled_desc'] = 'Kill switch. При «Нет» gateway отвечает 503 service_disabled.';

$_lang['setting_mxheadless.debug'] = 'Режим отладки';
$_lang['setting_mxheadless.debug_desc'] = 'Добавлять детали исключения в 500 problem+json.';

$_lang['setting_mxheadless.cache.enabled'] = 'HTTP-кэш ответов';
$_lang['setting_mxheadless.cache.enabled_desc'] = 'Кэшировать anonymous GET/HEAD с ETag.';

$_lang['setting_mxheadless.cache_ttl'] = 'TTL кэша (сек)';
$_lang['setting_mxheadless.cache_ttl_desc'] = 'max-age для публичных кэшированных ответов.';

$_lang['setting_mxheadless.rate_limit.enabled'] = 'Rate limiting';
$_lang['setting_mxheadless.rate_limit.enabled_desc'] = 'Лимиты запросов по identity/IP.';

$_lang['setting_mxheadless.rate_limit.max_requests'] = 'Лимит запросов по умолчанию';
$_lang['setting_mxheadless.rate_limit.max_requests_desc'] = 'Сколько запросов в окне, если у ключа нет своего лимита.';

$_lang['setting_mxheadless.rate_limit.window_seconds'] = 'Окно rate limit (сек)';
$_lang['setting_mxheadless.rate_limit.window_seconds_desc'] = 'Длина окна для лимита по умолчанию.';

$_lang['setting_mxheadless.cors.enabled'] = 'CORS включён';
$_lang['setting_mxheadless.cors.enabled_desc'] = 'Нужен для браузерного Nuxt/Next с другого origin.';

$_lang['setting_mxheadless.cors.allowed_origins'] = 'Разрешённые origins';
$_lang['setting_mxheadless.cors.allowed_origins_desc'] = 'Через запятую точные origins или *.';

$_lang['setting_mxheadless.cors.allowed_methods'] = 'Разрешённые методы';
$_lang['setting_mxheadless.cors.allowed_methods_desc'] = 'HTTP-методы для preflight через запятую.';

$_lang['setting_mxheadless.cors.allowed_headers'] = 'Разрешённые заголовки';
$_lang['setting_mxheadless.cors.allowed_headers_desc'] = 'Заголовки запроса, которые может слать клиент.';

$_lang['setting_mxheadless.cors.expose_headers'] = 'Expose-Headers';
$_lang['setting_mxheadless.cors.expose_headers_desc'] = 'Заголовки ответа, доступные JS (включая ETag).';

$_lang['setting_mxheadless.cors.allow_credentials'] = 'CORS credentials';
$_lang['setting_mxheadless.cors.allow_credentials_desc'] = 'Не сочетать с origins = *.';

$_lang['setting_mxheadless.max_body_bytes'] = 'Макс. размер тела (байт)';
$_lang['setting_mxheadless.max_body_bytes_desc'] = 'Отклонять более крупные тела запросов.';

$_lang['setting_mxheadless.max_uri_bytes'] = 'Макс. длина URI (байт)';
$_lang['setting_mxheadless.max_uri_bytes_desc'] = 'Лимит для path-параметра страницы.';

$_lang['setting_mxheadless.trusted_proxies'] = 'Доверенные прокси';
$_lang['setting_mxheadless.trusted_proxies_desc'] = 'IP/CIDR через запятую для X-Forwarded-* / IP клиента.';

$_lang['setting_mxheadless.idempotency.enabled'] = 'Идемпотентность';
$_lang['setting_mxheadless.idempotency.enabled_desc'] = 'Учитывать Idempotency-Key на POST (кэш 2xx).';

$_lang['setting_mxheadless.idempotency_ttl'] = 'TTL идемпотентности (сек)';
$_lang['setting_mxheadless.idempotency_ttl_desc'] = 'Сколько хранить результат по Idempotency-Key.';

$_lang['setting_mxheadless.webhook.max_attempts'] = 'Webhook: макс. попыток';
$_lang['setting_mxheadless.webhook.max_attempts_desc'] = 'Повторы доставки до отказа.';

$_lang['setting_mxheadless.webhook.worker_limit'] = 'Webhook: размер батча';
$_lang['setting_mxheadless.webhook.worker_limit_desc'] = 'Сколько доставок за один запуск worker.';

$_lang['setting_mxheadless.webhook.allow_private_urls'] = 'Webhook: private URL';
$_lang['setting_mxheadless.webhook.allow_private_urls_desc'] = 'Только для dev: localhost/private IP и отключение TLS verify.';

$_lang['setting_mxheadless.audit.enabled'] = 'Журнал аудита';
$_lang['setting_mxheadless.audit.enabled_desc'] = 'Писать обращения в mxheadless_api_log.';

$_lang['setting_mxheadless.audit.retention_days'] = 'Хранение аудита (дней)';
$_lang['setting_mxheadless.audit.retention_days_desc'] = 'Для CLI audit-prune.';

$_lang['setting_mxheadless.audit.log_get'] = 'Логировать GET';
$_lang['setting_mxheadless.audit.log_get_desc'] = 'При «Да» писать и безопасные GET (шумнее).';

$_lang['setting_mxheadless.oauth.enabled'] = 'OAuth token endpoint';
$_lang['setting_mxheadless.oauth.enabled_desc'] = 'Включить POST /auth/token для токенов mxt_*.';

$_lang['setting_mxheadless.oauth.token_ttl'] = 'TTL OAuth-токена (сек)';
$_lang['setting_mxheadless.oauth.token_ttl_desc'] = 'Время жизни выдаваемых opaque-токенов.';

$_lang['setting_mxheadless.oauth.password_grant_enabled'] = 'Password grant';
$_lang['setting_mxheadless.oauth.password_grant_enabled_desc'] = 'Разрешить password credentials (обычно оставьте «Нет»).';

$_lang['setting_mxheadless.swagger.enabled'] = 'Swagger UI';
$_lang['setting_mxheadless.swagger.enabled_desc'] = 'Интерактивная документация на GET /api/v1/docs. Сырой OpenAPI JSON остаётся доступен при отключении.';
