<?php

use MxHeadless\Model\modMxHeadlessApiKey;
use MODX\Revolution\Processors\Model\UpdateProcessor;

class mxHeadlessApiKeyUpdateProcessor extends UpdateProcessor
{
    public $classKey = modMxHeadlessApiKey::class;
    public $objectType = 'mxheadless_apikey';
    public $primaryKeyField = 'id';
    public $permission = 'mxheadless_apikeys';

    public function beforeSet()
    {
        $name = trim((string) $this->getProperty('name', ''));
        if ($name === '') {
            $this->addFieldError('name', $this->modx->lexicon('mxheadless_apikey_err_ns'));

            return false;
        }
        $this->setProperty('name', $name);

        $scopesRaw = trim((string) $this->getProperty('scopes', ''));
        $scopeList = array_values(array_filter(array_map('trim', explode(',', $scopesRaw))));
        if ($scopeList === []) {
            $scopeList = ['resources.read'];
        }
        $this->setProperty('scopes', $scopeList);

        $rateMax = $this->getProperty('rate_limit_max');
        if ($rateMax === '' || $rateMax === null) {
            $this->setProperty('rate_limit_max', null);
        } else {
            $this->setProperty('rate_limit_max', max(1, (int) $rateMax));
        }

        $rateWindow = $this->getProperty('rate_limit_window');
        if ($rateWindow === '' || $rateWindow === null) {
            $this->setProperty('rate_limit_window', null);
        } else {
            $this->setProperty('rate_limit_window', max(1, (int) $rateWindow));
        }

        $this->unsetProperty('secret_hash');
        $this->unsetProperty('lookup_id');
        $this->unsetProperty('token');

        return parent::beforeSet();
    }

    public function cleanup()
    {
        $row = $this->object->toArray();
        unset($row['secret_hash']);
        $scopes = $row['scopes'] ?? [];
        if (is_array($scopes)) {
            $row['scopes'] = implode(',', $scopes);
        }

        return $this->success('', $row);
    }
}

return 'mxHeadlessApiKeyUpdateProcessor';
