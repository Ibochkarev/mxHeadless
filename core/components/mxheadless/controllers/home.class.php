<?php

require_once dirname(__FILE__) . '/maincontroller.class.php';

class MxheadlessHomeManagerController extends MxheadlessMainController
{
    /**
     * @param array<string, mixed> $scriptProperties
     */
    public function process(array $scriptProperties = []): void
    {
    }

    public function getPageTitle(): string
    {
        return $this->modx->lexicon('mxheadless');
    }

    public function loadCustomCssJs(): void
    {
        $jsUrl = $this->mxHeadlessConfig['jsUrl'];
        $this->addJavascript($jsUrl . 'mgr/widgets/apikeys.grid.js');
        $this->addJavascript($jsUrl . 'mgr/widgets/home.panel.js');
        $this->addJavascript($jsUrl . 'mgr/sections/home.js');
        $this->addHtml('<script type="text/javascript">
        Ext.onReady(function() {
            MODx.load({xtype: "mxheadless-page-home"});
        });
        </script>');
    }

    public function getTemplateFile(): string
    {
        return $this->modx->getOption('core_path')
            . 'components/mxheadless/templates/home.tpl';
    }
}
