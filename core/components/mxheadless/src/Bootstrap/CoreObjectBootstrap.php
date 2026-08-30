<?php

declare(strict_types=1);

namespace MxHeadless\Bootstrap;

use MODX\Revolution\modCategory;
use MODX\Revolution\modChunk;
use MODX\Revolution\modContentType;
use MODX\Revolution\modContext;
use MODX\Revolution\modResource;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Definition\RelationDefinition;
use MxHeadless\Extension\ExtensionApi;

final class CoreObjectBootstrap
{
    public static function register(ExtensionApi $api): void
    {
        $api->registerObject(
            ObjectDefinition::create('resources')
                ->class(modResource::class)
                ->readable()
                ->creatable()
                ->updatable()
                ->deletable()
                ->fields([
                    'id',
                    'type',
                    'pagetitle',
                    'longtitle',
                    'description',
                    'alias',
                    'link_attributes',
                    'published',
                    'pub_date',
                    'unpub_date',
                    'parent',
                    'isfolder',
                    'introtext',
                    'content',
                    'richtext',
                    'template',
                    'menuindex',
                    'searchable',
                    'cacheable',
                    'createdby',
                    'createdon',
                    'editedby',
                    'editedon',
                    'deleted',
                    'deletedon',
                    'deletedby',
                    'publishedon',
                    'publishedby',
                    'menutitle',
                    'content_dispo',
                    'hidemenu',
                    'class_key',
                    'context_key',
                    'content_type',
                    'uri',
                    'uri_override',
                    'hide_children_in_tree',
                    'show_in_tree',
                    'properties',
                ])
                ->filterable([
                    'id',
                    'parent',
                    'published',
                    'deleted',
                    'context_key',
                    'template',
                    'alias',
                    'class_key',
                    'hidemenu',
                    'isfolder',
                    'menuindex',
                ])
                ->sorts(['id', 'menuindex', 'pagetitle', 'createdon', 'editedon', 'publishedon'])
                ->searchable(['pagetitle', 'longtitle', 'description', 'introtext', 'alias', 'uri'])
                ->hiddenFields(['properties'])
                ->protectedFields(['createdby', 'editedby', 'deletedby', 'publishedby'])
                ->immutableFields(['id', 'createdon', 'createdby', 'editedon', 'editedby', 'deletedon'])
                ->requiredFields(['pagetitle'])
                ->contexts(['web', 'mgr'])
        );

        $api->registerRelation(
            'resources',
            RelationDefinition::create('parent')
                ->to('resources')
                ->toOne()
                ->foreignKeyField('parent')
                ->localKeyField('id')
                ->fields(['id', 'pagetitle', 'alias', 'uri', 'parent']),
        );

        $api->registerRelation(
            'resources',
            RelationDefinition::create('children')
                ->to('resources')
                ->toMany()
                ->foreignKeyField('parent')
                ->localKeyField('id')
                ->fields(['id', 'pagetitle', 'alias', 'uri', 'parent', 'menuindex', 'hidemenu', 'published']),
        );

        $api->registerObject(
            ObjectDefinition::create('contexts')
                ->class(modContext::class)
                ->primaryKey('key')
                ->readable()
                ->contextAccessGated()
                ->fields(['key', 'name', 'description', 'rank'])
                ->filterable(['key'])
                ->sorts(['key', 'name', 'rank'])
                ->searchable(['key', 'name', 'description']),
        );

        $api->registerObject(
            ObjectDefinition::create('chunks')
                ->class(modChunk::class)
                ->readable()
                ->fields(['id', 'name', 'description', 'snippet', 'category', 'cache_type', 'locked'])
                ->filterable(['name', 'category'])
                ->sorts(['id', 'name'])
                ->searchable(['name', 'description', 'snippet'])
                ->hiddenFields(['properties']),
        );

        $api->registerObject(
            ObjectDefinition::create('templates')
                ->class(modTemplate::class)
                ->readable()
                ->fields([
                    'id',
                    'templatename',
                    'description',
                    'category',
                    'icon',
                    'template_type',
                    'content',
                    'locked',
                ])
                ->filterable(['templatename', 'category', 'locked'])
                ->sorts(['id', 'templatename', 'category'])
                ->searchable(['templatename', 'description'])
                ->hiddenFields(['properties']),
        );

        $api->registerObject(
            ObjectDefinition::create('snippets')
                ->class(modSnippet::class)
                ->readable()
                ->fields(['id', 'name', 'description', 'snippet', 'category', 'cache_type', 'locked'])
                ->filterable(['name', 'category', 'locked'])
                ->sorts(['id', 'name', 'category'])
                ->searchable(['name', 'description'])
                ->hiddenFields(['properties']),
        );

        $api->registerObject(
            ObjectDefinition::create('tvs')
                ->class(modTemplateVar::class)
                ->readable()
                ->fields([
                    'id',
                    'type',
                    'name',
                    'caption',
                    'description',
                    'category',
                    'locked',
                    'elements',
                    'rank',
                    'display',
                    'default_text',
                ])
                ->filterable(['name', 'type', 'category', 'locked'])
                ->sorts(['id', 'name', 'rank', 'category'])
                ->searchable(['name', 'caption', 'description'])
                ->hiddenFields(['properties', 'input_properties', 'output_properties']),
        );

        $api->registerObject(
            ObjectDefinition::create('categories')
                ->class(modCategory::class)
                ->readable()
                ->fields(['id', 'parent', 'category', 'rank'])
                ->filterable(['parent', 'category'])
                ->sorts(['id', 'category', 'rank', 'parent'])
                ->searchable(['category']),
        );

        $api->registerObject(
            ObjectDefinition::create('content_types')
                ->class(modContentType::class)
                ->readable()
                ->fields([
                    'id',
                    'name',
                    'description',
                    'mime_type',
                    'file_extensions',
                    'icon',
                    'binary',
                ])
                ->filterable(['name', 'mime_type', 'binary'])
                ->sorts(['id', 'name'])
                ->searchable(['name', 'description', 'mime_type', 'file_extensions'])
                ->hiddenFields(['headers']),
        );

        $categoryFields = ['id', 'parent', 'category', 'rank'];
        foreach (['chunks', 'templates', 'snippets', 'tvs'] as $element) {
            $api->registerRelation(
                $element,
                RelationDefinition::create('category')
                    ->to('categories')
                    ->toOne()
                    ->foreignKeyField('category')
                    ->localKeyField('id')
                    ->fields($categoryFields),
            );
        }

        $api->registerRelation(
            'categories',
            RelationDefinition::create('parent')
                ->to('categories')
                ->toOne()
                ->foreignKeyField('parent')
                ->localKeyField('id')
                ->fields($categoryFields),
        );
    }
}
