<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MODX\Revolution\modX;
use MxHeadless\Exception\NotFoundException;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Http\Psr7Factory;
use MxHeadless\Version;
use Psr\Http\Message\ResponseInterface;

final class SwaggerUiService
{
    private const CDN_VERSION = '5.17.14';

    public function __construct(
        private readonly modX $modx,
        private readonly ApiPrefix $apiPrefix,
    ) {
    }

    public function handle(): ResponseInterface
    {
        if (!(bool) $this->modx->getOption('mxheadless_swagger_enabled', null, true)) {
            throw new NotFoundException('Endpoint not found');
        }

        $specUrl = $this->apiPrefix->versioned() . '/meta/openapi.json';
        $cdn = 'https://unpkg.com/swagger-ui-dist@' . self::CDN_VERSION;
        $title = 'mxHeadless API ' . Version::STRING;
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedCdn = htmlspecialchars($cdn, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $specUrlJson = json_encode(
            $specUrl,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP,
        );

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$escapedTitle}</title>
  <link rel="stylesheet" href="{$escapedCdn}/swagger-ui.css">
  <style>
    body { margin: 0; background: #fafafa; }
    #swagger-ui { max-width: 1460px; margin: 0 auto; }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="{$escapedCdn}/swagger-ui-bundle.js" crossorigin></script>
  <script src="{$escapedCdn}/swagger-ui-standalone-preset.js" crossorigin></script>
  <script>
    window.ui = SwaggerUIBundle({
      url: {$specUrlJson},
      dom_id: '#swagger-ui',
      deepLinking: true,
      persistAuthorization: true,
      validatorUrl: null,
      presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
      layout: 'StandaloneLayout',
      tryItOutEnabled: true
    });
  </script>
</body>
</html>
HTML;

        return Psr7Factory::createResponse(
            200,
            [
                'Content-Type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'public, max-age=60',
            ],
            $html,
        );
    }
}
