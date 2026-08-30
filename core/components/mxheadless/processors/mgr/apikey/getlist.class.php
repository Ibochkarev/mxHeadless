<?php

use MxHeadless\Model\modMxHeadlessApiKey;
use MODX\Revolution\Processors\Model\GetListProcessor;
use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class mxHeadlessApiKeyGetListProcessor extends GetListProcessor
{
    public $classKey = modMxHeadlessApiKey::class;
    public $objectType = 'mxheadless_apikey';
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'DESC';
    public $permission = 'mxheadless_apikeys';

    public function initialize()
    {
        if (!$this->modx->hasPermission($this->permission) && !$this->modx->user->get('sudo')) {
            return $this->modx->lexicon('access_denied');
        }

        return parent::initialize();
    }

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $query = trim((string) $this->getProperty('query', ''));
        if ($query !== '') {
            $c->where([
                'name:LIKE' => '%' . $query . '%',
                'OR:lookup_id:LIKE' => '%' . $query . '%',
            ]);
        }

        $revoked = $this->getProperty('revoked');
        if ($revoked !== null && $revoked !== '') {
            $c->where(['revoked' => (bool) $revoked]);
        }

        return $c;
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareRow(xPDOObject $object)
    {
        $row = $object->toArray();
        unset($row['secret_hash']);

        $scopes = $row['scopes'] ?? [];
        if (is_array($scopes)) {
            $row['scopes'] = implode(',', $scopes);
        }

        $row['created_on_formatted'] = $this->formatTs($row['created_on'] ?? null);
        $row['last_used_on_formatted'] = $this->formatTs($row['last_used_on'] ?? null);

        return $row;
    }

    private function formatTs(mixed $value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        if (is_numeric($value)) {
            $ts = (int) $value;

            return $ts > 0 ? date('Y-m-d H:i', $ts) : '';
        }

        $ts = strtotime((string) $value);

        return $ts ? date('Y-m-d H:i', $ts) : (string) $value;
    }
}

return 'mxHeadlessApiKeyGetListProcessor';
