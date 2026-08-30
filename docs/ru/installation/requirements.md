# Требования

## Среда

- MODX Revolution **3.2.3+** (PHP **8.1+**)
- xPDO **~3.1** на стабильной ветке; **^3.2** на актуальной `3.x`
- MySQL/MariaDB с InnoDB
- Pretty URLs желательны для gateway `/api/v1`

## Опционально

- Cron или CLI для webhook worker
- HTTPS в production

## Матрица совместимости

| MODX | xPDO | PHP |
|------|------|-----|
| 3.2.3-pl | ~3.1 | 8.1–8.3 |
| 3.x (dev) | ^3.2 | 8.2+ |
