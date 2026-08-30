# Доверенные прокси

Когда MODX стоит за load balancer или reverse proxy, TCP peer — балансировщик, а не браузер. mxHeadless берёт client IP из `X-Forwarded-For` только если прямое соединение пришло с trusted peer.

## Настройка

| Ключ | По умолчанию | Формат |
|-----|--------------|--------|
| `mxheadless.trusted_proxies` | пусто | IP через запятую (CIDR core сейчас не парсит) |

Пример за одним nginx proxy:

```
10.0.0.5,10.0.0.6
```

Пустое значение: всегда `REMOTE_ADDR`. Forwarded headers игнорируются.

## Поведение

`TrustedProxyMiddleware` в начале stack:

1. Читает `REMOTE_ADDR` с веб-сервера.
2. Если совпал с записью в `mxheadless.trusted_proxies`, первый hop из `X-Forwarded-For` становится `client_ip`.
3. Иначе `client_ip` = `REMOTE_ADDR`.

Rate limiting использует `client_ip`. Audit log IP сегодня не хранит. Справедливость rate limit зависит от этой логики.

## Риски misconfiguration

| Ошибка | Эффект |
|--------|--------|
| Trust для всего интернета | Клиенты подделывают `X-Forwarded-For` и обходят rate limits |
| Нет IP балансировщика | Все пользователи делят один IP |
| Неверная цепочка заголовков | Первый XFF hop может быть недоверенным client |

Настройте nginx/Apache так, чтобы balancer перезаписывал или санировал `X-Forwarded-For`. В setting указывайте только egress IP balancer.

## TLS и host headers

mxHeadless не читает `X-Forwarded-Proto` для URL в core gateway. TLS на proxy. `site_url` / `base_url` MODX должны быть корректны.

## См. также

- [Settings](settings.md)
- [Rate limiting](../api/rate-limiting.md)
- [Deployment](../operations/deployment.md)
- [Troubleshooting](../operations/troubleshooting.md)
