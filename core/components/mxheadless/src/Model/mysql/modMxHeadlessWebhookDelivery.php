<?php

declare(strict_types=1);

namespace MxHeadless\Model\mysql;

class modMxHeadlessWebhookDelivery extends \MxHeadless\Model\modMxHeadlessWebhookDelivery
{
    public static $metaMap = [
        'package' => 'MxHeadless',
        'version' => '3.0',
        'table' => 'mxheadless_webhook_deliveries',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'id' => null,
            'subscription_id' => 0,
            'event' => '',
            'payload' => '',
            'url' => '',
            'secret' => '',
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'created_on' => 0,
            'delivered_on' => null,
            'next_attempt_on' => null,
        ],
        'fieldMeta' => [
            'id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'index' => 'pk', 'generated' => 'native'],
            'subscription_id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0, 'index' => 'index'],
            'event' => ['dbtype' => 'varchar', 'precision' => '128', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'payload' => ['dbtype' => 'mediumtext', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'url' => ['dbtype' => 'varchar', 'precision' => '2048', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'secret' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'status' => ['dbtype' => 'varchar', 'precision' => '32', 'phptype' => 'string', 'null' => false, 'default' => 'pending', 'index' => 'index'],
            'attempts' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
            'last_error' => ['dbtype' => 'text', 'phptype' => 'string', 'null' => true],
            'created_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0],
            'delivered_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => true],
            'next_attempt_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => true, 'index' => 'index'],
        ],
        'indexes' => [
            'status' => ['alias' => 'status', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['status' => []]],
            'subscription_id' => ['alias' => 'subscription_id', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['subscription_id' => []]],
            'next_attempt_on' => ['alias' => 'next_attempt_on', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['next_attempt_on' => []]],
            'PRIMARY' => ['alias' => 'PRIMARY', 'primary' => true, 'unique' => true, 'type' => 'BTREE', 'columns' => ['id' => []]],
        ],
    ];
}
