<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Media;

use MODX\Revolution\modX;
use MxHeadless\Media\MediaUrlResolver;
use PHPUnit\Framework\TestCase;

final class MediaUrlResolverTest extends TestCase
{
    public function testAbsoluteHttpsUrlReturnedAsIs(): void
    {
        $resolver = new MediaUrlResolver(new modX());
        $url = 'https://cdn.example.test/assets/photo.jpg';

        self::assertSame($url, $resolver->resolve($url));
    }

    public function testRelativePathPrependsSiteUrl(): void
    {
        $resolver = new MediaUrlResolver(new modX(['site_url' => 'https://example.test/']));
        $path = 'assets/images/logo.png';

        self::assertSame('https://example.test/assets/images/logo.png', $resolver->resolve($path));
    }
}
