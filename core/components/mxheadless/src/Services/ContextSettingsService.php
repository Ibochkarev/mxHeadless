<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MODX\Revolution\modX;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

final class ContextSettingsService
{
    /** @var list<string> */
    private const PUBLIC_OPTIONS = [
        'site_url',
        'base_url',
        'http_host',
        'site_start',
        'error_page',
        'unauthorized_page',
        'cultureKey',
        'locale',
    ];

    public function __construct(
        private readonly modX $modx,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function get(ServerRequestInterface $request, string $key): array
    {
        $this->authorizer->assertContextKeyAccess($request, $key);

        $context = $this->modx->getContext($key);
        if ($context === null) {
            throw new NotFoundException('Context not found');
        }

        $settings = [];
        foreach (self::PUBLIC_OPTIONS as $option) {
            $settings[$option] = (string) $context->getOption($option, null, '');
        }

        return [
            'data' => [
                'key' => $key,
                'settings' => $settings,
            ],
            'meta' => [],
        ];
    }
}
