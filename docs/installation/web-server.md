# Web server configuration

## Apache

Enable `mod_rewrite`. MODX friendly URLs must route unknown paths to `index.php`. Gateway prefix defaults to `/api` (`mxheadless.api.prefix`).

## Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

Ensure `/api/v1/*` reaches MODX `index.php`.

## Fallback entry

If rewrite is unavailable, use `assets/components/mxheadless/api.php`.
