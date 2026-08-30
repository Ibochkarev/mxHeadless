<?php

declare(strict_types=1);

namespace MxHeadless\Model\mysql;

class modMxHeadlessOAuthToken extends \MxHeadless\Model\modMxHeadlessOAuthToken
{
    public static $metaMap = [
        'package' => 'MxHeadless',
        'version' => '3.0',
        'table' => 'mxheadless_oauth_tokens',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'id' => null,
            'token_id' => '',
            'token_hash' => '',
            'client_id' => 0,
            'user_id' => null,
            'scopes' => null,
            'expires_on' => 0,
            'revoked' => false,
            'created_on' => 0,
            'last_used_on' => null,
        ],
        'fieldMeta' => [
            'id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'index' => 'pk', 'generated' => 'native'],
            'token_id' => ['dbtype' => 'varchar', 'precision' => '32', 'phptype' => 'string', 'null' => false, 'index' => 'unique'],
            'token_hash' => ['dbtype' => 'varchar', 'precision' => '255', 'phptype' => 'string', 'null' => false],
            'client_id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0, 'index' => 'index'],
            'user_id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => true],
            'scopes' => ['dbtype' => 'text', 'phptype' => 'json', 'null' => true],
            'expires_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0, 'index' => 'index'],
            'revoked' => ['dbtype' => 'tinyint', 'precision' => '1', 'attributes' => 'unsigned', 'phptype' => 'boolean', 'null' => false, 'default' => 0, 'index' => 'index'],
            'created_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => false, 'default' => 0],
            'last_used_on' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'timestamp', 'null' => true],
        ],
        'indexes' => [
            'token_id' => ['alias' => 'token_id', 'primary' => false, 'unique' => true, 'type' => 'BTREE', 'columns' => ['token_id' => []]],
            'client_id' => ['alias' => 'client_id', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['client_id' => []]],
            'expires_on' => ['alias' => 'expires_on', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['expires_on' => []]],
            'revoked' => ['alias' => 'revoked', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['revoked' => []]],
            'PRIMARY' => ['alias' => 'PRIMARY', 'primary' => true, 'unique' => true, 'type' => 'BTREE', 'columns' => ['id' => []]],
        ],
    ];
}
