<?php

declare(strict_types=1);

namespace MxHeadless\Model\mysql;

class modMxHeadlessApiLog extends \MxHeadless\Model\modMxHeadlessApiLog
{
    public static $metaMap = [
        'package' => 'MxHeadless',
        'version' => '3.0',
        'table' => 'mxheadless_api_log',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'id' => null,
            'request_id' => '',
            'identity_key' => '',
            'api_key_id' => null,
            'method' => '',
            'path' => '',
            'context_key' => '',
            'status_code' => 0,
            'duration_ms' => 0,
            'created_on' => 0,
        ],
        'fieldMeta' => [
            'id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'index' => 'pk', 'generated' => 'native'],
            'request_id' => ['dbtype' => 'varchar', 'precision' => '64', 'phptype' => 'string', 'null' => false, 'default' => '', 'index' => 'index'],
            'identity_key' => ['dbtype' => 'varchar', 'precision' => '128', 'phptype' => 'string', 'null' => false, 'default' => '', 'index' => 'index'],
            'api_key_id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => true, 'index' => 'index'],
            'method' => ['dbtype' => 'varchar', 'precision' => '8', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'path' => ['dbtype' => 'varchar', 'precision' => '2048', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'context_key' => ['dbtype' => 'varchar', 'precision' => '64', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'status_code' => ['dbtype' => 'smallint', 'precision' => '5', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
            'duration_ms' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
            'created_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0, 'index' => 'index'],
        ],
        'indexes' => [
            'request_id' => ['alias' => 'request_id', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['request_id' => []]],
            'identity_key' => ['alias' => 'identity_key', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['identity_key' => []]],
            'api_key_id' => ['alias' => 'api_key_id', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['api_key_id' => []]],
            'created_on' => ['alias' => 'created_on', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['created_on' => []]],
            'PRIMARY' => ['alias' => 'PRIMARY', 'primary' => true, 'unique' => true, 'type' => 'BTREE', 'columns' => ['id' => []]],
        ],
    ];
}
