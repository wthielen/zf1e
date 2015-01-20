<?php

/**
 * The Active Module Bootstrapper
 *
 * This makes sure that the modules are actually contained in their own
 * configurations. The problem with the default ZF1 module bootstrapping is
 * that all the bootstrappers of all the modules are being run, even though
 * only one module is being accessed/used. This is a waste of resources and
 * runtime overhead.
 *
 * This blog deals with this:
 * http://offshootinc.com/blog/2011/02/11/modul-bootstrapping-in-zend-framework/
 *
 * And this plugin and other code are based on this blog.
 *
 * So instead of a Bootstrap.php, we'll have an Initializer.php in the module
 * directories.
 */
class ZFE_Plugin_ActiveModule extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $activeModule = $request->getModuleName();
        $activeBootstrap = $this->_getActiveBootstrap($activeModule);

        if ($activeBootstrap instanceof ZFE_Module_Bootstrap) {
            $className = ucfirst($activeModule) . '_Bootstrap_Initializer';

            if (class_exists($className)) {
                $init = new $className($activeBootstrap);
                $init->initialize();
            }
        }
    }

    protected function _getActiveBootstrap($name)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $moduleList = $bootstrap->getResource('modules');

        return isset($moduleList[$name]) ? $moduleList[$name] : null;
    }
}
