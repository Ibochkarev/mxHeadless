<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Support;

use MxHeadless\Definition\ObjectDefinition;

final class TestDefinitions
{
    public static function article(): ObjectDefinition
    {
        return ObjectDefinition::create('articles')
            ->setName('articles')
            ->class('Article')
            ->fields(['id', 'title', 'body', 'published', 'deleted', 'parent'])
            ->filterable(['id', 'title', 'published', 'parent'])
            ->sorts(['id', 'title', 'published'])
            ->searchable(['title', 'body'])
            ->readable();
    }

    public static function resource(): ObjectDefinition
    {
        return ObjectDefinition::create('resources')
            ->setName('resources')
            ->class('modResource')
            ->fields(['id', 'pagetitle', 'alias', 'uri', 'parent', 'published', 'deleted'])
            ->filterable(['parent', 'published', 'alias'])
            ->sorts(['id', 'pagetitle', 'published'])
            ->searchable(['pagetitle', 'alias'])
            ->readable()
            ->creatable()
            ->updatable()
            ->deletable();
    }
}
