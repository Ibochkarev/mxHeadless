<?php

declare(strict_types=1);

namespace MxHeadless\Model\mysql;

class modMxHeadlessWebhookSubscription extends \MxHeadless\Model\modMxHeadlessWebhookSubscription
{
    public static $metaMap = [
        'package' => 'MxHeadless',
        'version' => '3.0',
        'table' => 'mxheadless_webhook_subscriptions',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'id' => null,
            'name' => '',
            'url' => '',
            'secret' => '',
            'events' => null,
            'active' => true,
            'created_on' => 0,
        ],
        'fieldMeta' => [
            'id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'index' => 'pk', 'generated' => 'native'],
            'name' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'url' => ['dbtype' => 'varchar', 'precision' => '2048', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'secret' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'events' => ['dbtype' => 'text', 'phptype' => 'json', 'null' => true],
            'active' => ['dbtype' => 'tinyint', 'precision' => '1', 'attributes' => 'unsigned', 'phptype' => 'boolean', 'null' => false, 'default' => 1],
            'created_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0],
        ],
        'indexes' => [
            'active' => ['alias' => 'active', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['active' => []]],
            'PRIMARY' => ['alias' => 'PRIMARY', 'primary' => true, 'unique' => true, 'type' => 'BTREE', 'columns' => ['id' => []]],
        ],
    ];
}
