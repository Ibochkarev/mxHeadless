# Documentation style

mxHeadless docs follow two skills:

1. **modx-docs** (`~/.claude/skills/modx-docs/`) — facts from code, RU/EN parity, no invented API.
2. **humanizer** (`~/.agents/skills/humanizer/`) — remove AI filler; match [modx-docs writing-style](https://github.com/modx-pro) rules.

## Workflow

1. Read `RoutesRegistrar`, services, and `_build/elements/settings.php` before documenting an endpoint.
2. Draft EN page with examples that match `openapi.yaml`.
3. Translate to `docs/ru/` with the same structure. Do not leave one-line stubs.
4. Run humanizer pass: no em dashes, no «важно отметить», active voice, concrete setting keys.
5. If behavior is not implemented yet, say so explicitly.

## Do not

- Run `scripts/generate_docs.py` over hand-written pages (it overwrites expanded content).
- Document endpoints that are not in `RoutesRegistrar`.
- Promise GraphQL or arbitrary DB access; point readers to [architecture](architecture.md#9-objectregistry).

## Verify

- Internal links resolve between EN and RU trees.
- curl examples use `/api/v1` and real query parameter names from `QueryParser`.
