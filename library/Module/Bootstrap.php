<?php

class ZFE_Module_Bootstrap extends Zend_Application_Module_Bootstrap
{
    public function __construct($application)
    {
        parent::__construct($application);
        $this->_loadModuleConfig();
        $this->_loadInitializer();
    }

    protected function _loadModuleConfig()
    {
        $front = Zend_Controller_Front::getInstance();
        $moduleDir = $front->getModuleDirectory($this->getModuleName());

        $configFile = $moduleDir . "/module.ini";

        if (!file_exists($configFile)) return;

        $config = new Zend_Config_Ini($configFile, $this->getEnvironment());
        $this->setOptions($config->toArray());
    }

    protected function _loadInitializer()
    {
        $resourceLoader = $this->getResourceLoader();

        $resourceLoader->addResourceType(
            'Bootstrap_Initializer', 'bootstrap', 'Bootstrap'
        );
    }
}
