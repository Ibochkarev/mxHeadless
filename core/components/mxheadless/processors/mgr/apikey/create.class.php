<?php

use MxHeadless\Model\modMxHeadlessApiKey;
use MODX\Revolution\Processors\Processor;

class mxHeadlessApiKeyCreateProcessor extends Processor
{
    public $permission = 'mxheadless_apikeys';

    public function checkPermissions()
    {
        return $this->modx->hasPermission($this->permission) || $this->modx->user->get('sudo');
    }

    public function getLanguageTopics()
    {
        return ['mxheadless:default'];
    }

    public function process()
    {
        $name = trim((string) $this->getProperty('name', ''));
        if ($name === '') {
            return $this->failure($this->modx->lexicon('mxheadless_apikey_err_ns'));
        }

        $scopesRaw = trim((string) $this->getProperty('scopes', 'resources.read'));
        $scopeList = array_values(array_filter(array_map('trim', explode(',', $scopesRaw))));
        if ($scopeList === []) {
            $scopeList = ['resources.read'];
        }

        $lookupId = bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(16));
        $plain = 'mxh_' . $lookupId . '_' . $secret;

        /** @var modMxHeadlessApiKey|null $model */
        $model = $this->modx->newObject(modMxHeadlessApiKey::class);
        if ($model === null) {
            return $this->failure($this->modx->lexicon('mxheadless_apikey_err_save'));
        }

        $data = [
            'lookup_id' => $lookupId,
            'secret_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'name' => $name,
            'scopes' => $scopeList,
            'revoked' => false,
            'created_on' => time(),
            'created_by' => (int) $this->modx->user->get('id'),
        ];

        $rateMax = $this->getProperty('rate_limit_max');
        if ($rateMax !== null && $rateMax !== '') {
            $data['rate_limit_max'] = max(1, (int) $rateMax);
        }
        $rateWindow = $this->getProperty('rate_limit_window');
        if ($rateWindow !== null && $rateWindow !== '') {
            $data['rate_limit_window'] = max(1, (int) $rateWindow);
        }

        $model->fromArray($data);
        if (!$model->save()) {
            return $this->failure($this->modx->lexicon('mxheadless_apikey_err_save'));
        }

        $row = $model->toArray();
        unset($row['secret_hash']);
        $row['scopes'] = implode(',', $scopeList);
        $row['token'] = $plain;

        return $this->success('', $row);
    }
}

return 'mxHeadlessApiKeyCreateProcessor';
