# Медиа URL

Пути к файлам в полях ресурсов превращаются в абсолютные URL через media sources MODX. Сырые пути на диск в ответе не попадают.

## Разрешение

`MediaUrlResolver` берёт default `modMediaSource`, вызывает `initialize()`, затем `getObjectUrl($path)`.

Относительные пути собираются через `site_url` и активный контекст.

## Абсолютные URL

Значения с `http://` или `https://` не меняются.

## Безопасность

SSRF-политика относится к webhook. Media resolver влияет только на JSON контента, не на исходящий HTTP на произвольные хосты.

## См. также

- [Resources](resources.md)
- [Безопасность](../security.md)
