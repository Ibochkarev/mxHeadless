# Requirements

## Runtime

- MODX Revolution **3.2.3+** (PHP **8.1+**)
- xPDO **~3.1** on stable MODX; **^3.2** on current `3.x` branch
- MySQL/MariaDB with InnoDB
- Pretty URLs recommended for `/api/v1` gateway

## Optional

- Cron or CLI for webhook retry worker
- HTTPS in production

## Compatibility matrix

| MODX | xPDO | PHP |
|------|------|-----|
| 3.2.3-pl | ~3.1 | 8.1–8.3 |
| 3.x (dev) | ^3.2 | 8.2+ |
