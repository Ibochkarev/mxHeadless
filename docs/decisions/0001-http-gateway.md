# mxHeadless HTTP Gateway

## Status

Accepted

## Context

MODX 3 does not provide a PSR-15 server stack for incoming requests. mxHeadless needs clean `/api/v1` URLs without modifying MODX core.

## Decision

Use `OnHandleRequest` plugin as primary gateway with fallback `assets/components/mxheadless/api.php`.

## Consequences

- Requires friendly URLs or fallback controller
- Plugin must exit before MODX resource resolution
- Same `Application` class for both entry points
