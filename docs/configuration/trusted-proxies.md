# Trusted proxies

When MODX sits behind a load balancer or reverse proxy, the TCP peer IP is the balancer, not the browser. mxHeadless resolves the client IP from `X-Forwarded-For` only when the direct connection comes from a trusted peer.

## Setting

| Key | Default | Format |
|-----|---------|--------|
| `mxheadless_trusted_proxies` | empty | Comma-separated IPs (no CIDR parsing in core today) |

Example behind one nginx proxy:

```
10.0.0.5,10.0.0.6
```

Empty value means: always use `REMOTE_ADDR`. Forwarded headers are ignored.

## Behavior

`TrustedProxyMiddleware` runs early in the stack:

1. Read `REMOTE_ADDR` from the web server.
2. If it matches an entry in `mxheadless_trusted_proxies`, take the first hop from `X-Forwarded-For` as `client_ip`.
3. Otherwise set `client_ip` to `REMOTE_ADDR`.

Downstream middleware uses `client_ip` for rate limiting. Audit log does not store IP today, but rate limit fairness depends on this value.

## Misconfiguration risks

| Mistake | Effect |
|---------|--------|
| Trust the public internet | Clients spoof `X-Forwarded-For` and bypass rate limits |
| Omit balancer IPs | All users share one IP (the balancer) |
| Wrong header chain | First XFF hop may be an untrusted client if the balancer appends incorrectly |

Fix nginx/Apache so the balancer overwrites or sanitizes `X-Forwarded-For`, then list only balancer egress IPs in the setting.

## TLS and host headers

mxHeadless does not read `X-Forwarded-Proto` for URL generation in the core gateway. Terminate TLS at the proxy and configure MODX `site_url` / `base_url` correctly.

## Related

- [Settings](settings.md)
- [Rate limiting](../api/rate-limiting.md)
- [Deployment](../operations/deployment.md)
- [Troubleshooting](../operations/troubleshooting.md)
- [MiniShop3 coexistence](../extensions/minishop3.md#coexistence-mxheadless--ms3-web-api)
