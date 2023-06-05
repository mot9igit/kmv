<?php

/**
 * The home manager controller for kmv.
 *
 */
class kmvHomeManagerController extends modExtraManagerController
{
    /** @var kmv $kmv */
    public $kmv;


    /**
     *
     */
    public function initialize()
    {
        $this->kmv = $this->modx->getService('kmv', 'kmv', MODX_CORE_PATH . 'components/kmv/model/');
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['kmv:default'];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('kmv');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->kmv->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/kmv.js');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/widgets/items.grid.js');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/widgets/items.windows.js');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->kmv->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        kmv.config = ' . json_encode($this->kmv->config) . ';
        kmv.config.connector_url = "' . $this->kmv->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "kmv-page-home"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="kmv-panel-home-div"></div>';

        return '';
    }
}