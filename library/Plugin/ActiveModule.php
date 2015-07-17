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
 * So next to a Bootstrap.php, we'll also have a bootstrap/Initializer.php in the
 * module directories.
 */
class ZFE_Plugin_ActiveModule extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->_switchErrorHandler($request);

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
        $moduleList = ZFE_Environment::getResource('modules')->getExecutedBootstraps();

        return isset($moduleList[$name]) ? $moduleList[$name] : null;
    }

    /**
     * Switch error handler based on currently active module
     *
     * Source: http://stackoverflow.com/a/2720316/2038785
     */
    protected function _switchErrorHandler($request)
    {
        $front = Zend_Controller_Front::getInstance();
        $activeModule = $request->getModuleName();

        $error = $front->getPlugin('Zend_Controller_Plugin_ErrorHandler');
        if (!($error instanceof Zend_Controller_Plugin_ErrorHandler)) return;

        $testRequest = new Zend_Controller_Request_Http();
        $testRequest->setModuleName($activeModule)
            ->setControllerName($error->getErrorHandlerController())
            ->setActionName($error->getErrorHandlerAction());

        if ($front->getDispatcher()->isDispatchable($testRequest)) {
            $error->setErrorHandlerModule($activeModule);
        }
    }
}
