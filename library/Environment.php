<?php

/**
 * A helper class to tell us more about the application environment in a Zend Framework
 * application.
 */
final class ZFE_Environment
{
    // The application autoloader
    private static $autoloader;

    /**
     * Get the application autoloader
     *
     * This is the autoloader of class Zend_Application_Module_Autoloader.
     * In a default Zend application, there is one.
     * TODO I am not sure about modularized Zend applications - maybe there is
     * one per module, but I don't have a modularized example to test this with
     */
    public static function getApplicationAutoloader()
    {
        if (is_null(self::$autoloader)) {
            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloaders = $autoloader->getAutoloaders();

            self::$autoloader = array_reduce($autoloader->getAutoloaders(), function($u, $v) {
                return $u instanceof Zend_Application_Module_Autoloader ? $u : $v;
            });
        }

        return self::$autoloader;
    }

    /**
     * Get the application namespace
     *
     * It takes the namespace set in the application's autoloader and
     * returns this.
     */
    public static function getApplicationNamespace()
    {
        $autoloader = self::getApplicationAutoloader();

        return $autoloader ? $autoloader->getNamespace() : '';
    }

    /**
     * Resource namespace/prefix
     *
     * This function will be useful in object models, as some objects
     * will want to refer to other objects, and it will be good if the
     * library knows which class name to use for these objects.
     *
     * This function returns the namespace reserved for the given type.
     */
    public static function getResourcePrefix($type)
    {
        $autoloader = self::getApplicationAutoloader();

        if (!$autoloader->hasResourceType($type)) return '';

        $resourceTypes = $autoloader->getResourceTypes();
        return $resourceTypes[$type]['namespace'];
    }
}
