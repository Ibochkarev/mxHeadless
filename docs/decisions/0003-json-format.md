# JSON Response Format

## Status

Accepted

## Decision

Use custom envelope `{ data, meta, links }` for success responses and RFC 9457 for errors.

## Rationale

Simpler for Nuxt/Next/SvelteKit clients. Avoids JSON:API complexity in v1.
