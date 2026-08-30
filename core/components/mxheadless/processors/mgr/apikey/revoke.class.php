<?php

use MxHeadless\Model\modMxHeadlessApiKey;
use MODX\Revolution\Processors\Processor;

class mxHeadlessApiKeyRevokeProcessor extends Processor
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
        $id = (int) $this->getProperty('id', 0);
        if ($id <= 0) {
            return $this->failure($this->modx->lexicon('mxheadless_apikey_err_nf'));
        }

        /** @var modMxHeadlessApiKey|null $model */
        $model = $this->modx->getObject(modMxHeadlessApiKey::class, $id);
        if (!$model instanceof modMxHeadlessApiKey) {
            return $this->failure($this->modx->lexicon('mxheadless_apikey_err_nf'));
        }

        $model->set('revoked', true);
        if (!$model->save()) {
            return $this->failure($this->modx->lexicon('mxheadless_apikey_err_save'));
        }

        return $this->success('');
    }
}

return 'mxHeadlessApiKeyRevokeProcessor';
