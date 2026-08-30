<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Http;

use MxHeadless\Http\PageUriResolver;
use PHPUnit\Framework\TestCase;

final class PageUriResolverTest extends TestCase
{
    public function testIndexAliases(): void
    {
        self::assertSame(['index.html', 'index', ''], PageUriResolver::candidates(''));
        self::assertSame(['index.html', 'index', ''], PageUriResolver::candidates('index'));
        self::assertSame(['index.html', 'index', ''], PageUriResolver::candidates('/index/'));
    }

    public function testAddsHtmlWhenMissing(): void
    {
        self::assertSame(
            ['about', 'about.html', 'about/'],
            PageUriResolver::candidates('about'),
        );
        self::assertSame(
            ['blog/post', 'blog/post.html', 'blog/post/'],
            PageUriResolver::candidates('blog/post'),
        );
    }

    public function testStripsHtmlExtension(): void
    {
        self::assertSame(
            ['about.html', 'about'],
            PageUriResolver::candidates('about.html'),
        );
    }

    public function testContainerTrailingSlash(): void
    {
        self::assertSame(
            ['blog/', 'blog', 'blog.html'],
            PageUriResolver::candidates('blog/'),
        );
    }
}
