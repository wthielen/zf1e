<?php

class ZFE_Module_Bootstrap extends Zend_Application_Module_Bootstrap
{
    public function __construct($application)
    {
        parent::__construct($application);
        $this->_loadInitializer();
    }

    protected function _loadInitializer()
    {
        $resourceLoader = $this->getResourceLoader();

        $resourceLoader->addResourceType(
            'Bootstrap_Initializer', 'bootstrap', 'Bootstrap'
        );
    }
}
