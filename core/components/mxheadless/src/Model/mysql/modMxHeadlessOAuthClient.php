<?php

declare(strict_types=1);

namespace MxHeadless\Model\mysql;

class modMxHeadlessOAuthClient extends \MxHeadless\Model\modMxHeadlessOAuthClient
{
    public static $metaMap = [
        'package' => 'MxHeadless',
        'version' => '3.0',
        'table' => 'mxheadless_oauth_clients',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'id' => null,
            'client_id' => '',
            'client_secret_hash' => '',
            'name' => '',
            'scopes' => null,
            'grant_types' => null,
            'revoked' => false,
            'created_on' => 0,
            'rate_limit_max' => null,
            'rate_limit_window' => null,
        ],
        'fieldMeta' => [
            'id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'index' => 'pk', 'generated' => 'native'],
            'client_id' => ['dbtype' => 'varchar', 'precision' => '64', 'phptype' => 'string', 'null' => false, 'index' => 'unique'],
            'client_secret_hash' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false],
            'name' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'scopes' => ['dbtype' => 'text', 'phptype' => 'json', 'null' => true],
            'grant_types' => ['dbtype' => 'text', 'phptype' => 'json', 'null' => true],
            'revoked' => ['dbtype' => 'tinyint', 'precision' => '1', 'attributes' => 'unsigned', 'phptype' => 'boolean', 'null' => false, 'default' => 0, 'index' => 'index'],
            'created_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0],
            'rate_limit_max' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => true],
            'rate_limit_window' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => true],
        ],
        'indexes' => [
            'client_id' => ['alias' => 'client_id', 'primary' => false, 'unique' => true, 'type' => 'BTREE', 'columns' => ['client_id' => []]],
            'revoked' => ['alias' => 'revoked', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['revoked' => []]],
            'PRIMARY' => ['alias' => 'PRIMARY', 'primary' => true, 'unique' => true, 'type' => 'BTREE', 'columns' => ['id' => []]],
        ],
    ];
}
