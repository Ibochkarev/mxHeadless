<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Services;

use MODX\Revolution\modX;
use MxHeadless\Exception\NotFoundException;
use MxHeadless\Http\ApiPrefix;
use MxHeadless\Services\SwaggerUiService;
use PHPUnit\Framework\TestCase;

final class SwaggerUiServiceTest extends TestCase
{
    public function testRendersHtmlWhenEnabled(): void
    {
        $modx = new modX([
            'mxheadless_swagger_enabled' => true,
            'mxheadless_api_prefix' => '/api',
        ]);

        $response = (new SwaggerUiService($modx, new ApiPrefix($modx)))->handle();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $html = (string) $response->getBody();
        self::assertStringContainsString('SwaggerUIBundle', $html);
        self::assertStringContainsString('/api/v1/meta/openapi.json', $html);
        self::assertStringContainsString('validatorUrl: null', $html);
    }

    public function testReturnsNotFoundWhenDisabled(): void
    {
        $modx = new modX([
            'mxheadless_swagger_enabled' => false,
        ]);

        $this->expectException(NotFoundException::class);
        (new SwaggerUiService($modx, new ApiPrefix($modx)))->handle();
    }
}
