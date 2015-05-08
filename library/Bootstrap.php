<?php

/**
 * Library bootstrapper class to be called from ZF's bootstrap
 * Put this in your Bootstrap.php file:

public function _initZFE()
{
    ZFE_Bootstrap::run();
}

 **/
class ZFE_Bootstrap
{
    protected static $bootstrap;

    public static function run($bootstrap)
    {
        ZFE_Util_Stopwatch::start('page');

        self::$bootstrap = $bootstrap;

        $methods = get_class_methods(get_called_class());
        foreach($methods as $method) {
            if (strpos($method, "_init") === 0) self::$method();
        }
    }

    private static function _initLibrary()
    {
        // Register the ZFE controller plugins as helpers
        Zend_Controller_Action_HelperBroker::addPath(
            LIBRARY_PATH . '/ZFE/Controller/Helpers',
            'ZFE_Controller_Helper'
        );

        // Load the ZFE_Plugin_ActiveModule plugin
        self::$bootstrap->bootstrap('FrontController');
        $front = self::$bootstrap->getResource('FrontController');
        $front->registerPlugin(new ZFE_Plugin_ActiveModule());
    }
}
