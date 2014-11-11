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
    public static function run($bootstrap)
    {
        // Register the ZFE controller plugins as helpers
        Zend_Controller_Action_HelperBroker::addPath(
            LIBRARY_PATH . '/ZFE/Controller/Helpers',
            'ZFE_Controller_Helper'
        );
    }
}
