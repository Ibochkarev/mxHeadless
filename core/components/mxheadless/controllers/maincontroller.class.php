<?php

/**
 * Shared mgr controller bootstrap for mxHeadless.
 */

abstract class MxheadlessMainController extends modExtraManagerController
{
    /** @var array<string, mixed> */
    public array $mxHeadlessConfig = [];

    public function initialize(): void
    {
        $corePath = $this->modx->getOption(
            'mxheadless.core_path',
            null,
            $this->modx->getOption('core_path') . 'components/mxheadless/',
        );
        $assetsUrl = $this->modx->getOption(
            'mxheadless.assets_url',
            null,
            $this->modx->getOption('assets_url') . 'components/mxheadless/',
        );

        $namespace = ['path' => $corePath];
        $bootstrap = $corePath . 'bootstrap.php';
        if (is_file($bootstrap)) {
            require_once $bootstrap;
        }

        $this->mxHeadlessConfig = [
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl . 'connector.php',
            'jsUrl' => $assetsUrl . 'js/',
        ];

        $this->addJavascript($this->mxHeadlessConfig['jsUrl'] . 'mgr/mxheadless.js');
        $this->addHtml('<script type="text/javascript">
        Ext.onReady(function() {
            MxHeadless.config = ' . $this->modx->toJSON($this->mxHeadlessConfig) . ';
        });
        </script>');

        parent::initialize();
    }

    /**
     * @return list<string>
     */
    public function getLanguageTopics(): array
    {
        return ['mxheadless:default'];
    }

    public function checkPermissions(): bool
    {
        return $this->modx->hasPermission('mxheadless_apikeys')
            || $this->modx->user->get('sudo');
    }
}
