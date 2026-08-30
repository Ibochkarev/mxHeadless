<?php

declare(strict_types=1);

namespace MxHeadless\Media;

use MODX\Revolution\modX;
use MODX\Revolution\Sources\modMediaSource;

final class MediaUrlResolver
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function resolve(string $path, string $context = 'web'): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        /** @var modMediaSource|null $source */
        $source = $this->modx->getObject(modMediaSource::class, ['is_default' => true]);
        if ($source instanceof modMediaSource) {
            $source->initialize();
            $url = $source->getObjectUrl($path);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        $siteUrl = (string) $this->modx->getOption('site_url', null, '/');
        if ($this->modx->context && $this->modx->context->get('key') !== $context) {
            $contextInstance = $this->modx->getContext($context);
            if ($contextInstance) {
                $siteUrl = (string) $contextInstance->getOption('site_url', $siteUrl);
            }
        }

        return rtrim($siteUrl, '/') . '/' . ltrim($path, '/');
    }
}
