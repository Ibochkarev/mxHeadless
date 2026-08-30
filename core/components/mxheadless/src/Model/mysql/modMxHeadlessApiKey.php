<?php

declare(strict_types=1);

namespace MxHeadless\Model\mysql;

class modMxHeadlessApiKey extends \MxHeadless\Model\modMxHeadlessApiKey
{
    public static $metaMap = [
        'package' => 'MxHeadless',
        'version' => '3.0',
        'table' => 'mxheadless_api_keys',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'id' => null,
            'lookup_id' => '',
            'secret_hash' => '',
            'name' => '',
            'scopes' => null,
            'expires_on' => null,
            'revoked' => false,
            'created_on' => 0,
            'created_by' => 0,
            'last_used_on' => null,
            'rate_limit_max' => null,
            'rate_limit_window' => null,
        ],
        'fieldMeta' => [
            'id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'index' => 'pk', 'generated' => 'native'],
            'lookup_id' => ['dbtype' => 'varchar', 'precision' => '32', 'phptype' => 'string', 'null' => false, 'index' => 'unique'],
            'secret_hash' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false],
            'name' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'scopes' => ['dbtype' => 'text', 'phptype' => 'json', 'null' => true],
            'expires_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => true],
            'revoked' => ['dbtype' => 'tinyint', 'precision' => '1', 'attributes' => 'unsigned', 'phptype' => 'boolean', 'null' => false, 'default' => 0, 'index' => 'index'],
            'created_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0],
            'created_by' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
            'last_used_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => true],
            'rate_limit_max' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => true],
            'rate_limit_window' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => true],
        ],
        'indexes' => [
            'lookup_id' => ['alias' => 'lookup_id', 'primary' => false, 'unique' => true, 'type' => 'BTREE', 'columns' => ['lookup_id' => []]],
            'revoked' => ['alias' => 'revoked', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['revoked' => []]],
            'expires_on' => ['alias' => 'expires_on', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['expires_on' => []]],
            'PRIMARY' => ['alias' => 'PRIMARY', 'primary' => true, 'unique' => true, 'type' => 'BTREE', 'columns' => ['id' => []]],
        ],
    ];
}
