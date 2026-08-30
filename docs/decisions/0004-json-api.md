# JSON:API Evaluation

## Status

Rejected for v1

## Evaluation

| Criterion | Custom JSON | JSON:API |
|-----------|-------------|----------|
| Frontend usability | High | Medium |
| xPDO mapping complexity | Low | High |
| N+1 / includes | Flexible | Strict compound docs |
| Ecosystem | OpenAPI + TS gen | jsonapi.org tooling |
| Performance | Better control | Heavier payloads |

## Decision

Custom JSON for v1. Serializer interface allows optional JSON:API content type in future.
