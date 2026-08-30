# Webhook Outbox

## Status

Accepted

## Decision

Transactional outbox table written in same DB transaction as mutation. Delivery via shutdown hook + cron worker with exponential backoff.
