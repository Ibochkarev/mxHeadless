# Media URLs

File paths in resource fields are resolved to absolute URLs through MODX media sources. Responses do not expose raw filesystem paths.

## Resolution

`MediaUrlResolver` loads the default `modMediaSource`, calls `initialize()`, then `getObjectUrl($path)`.

Relative paths become site URLs using `site_url` and the active context.

## Absolute URLs

Values that already start with `http://` or `https://` pass through unchanged.

## Security

Webhook delivery uses a separate SSRF policy. Media resolution only affects JSON output for content fields, not outbound HTTP from mxHeadless to arbitrary hosts.

## Related

- [Resources](resources.md)
- [Security](../security.md)
