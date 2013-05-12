<?php

/**
 * A base model class for classes which become Mongo documents.
 *
 * For now, it registers the Mongo application plugin resource, and
 * initializes it, when a connection is requested. Because this class 
 * does it in the constructor, nothing needs to be done in the 
 * application's bootstrapper. Just create a Mongo document object :)
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    protected static $conn;

    public function __construct()
    {
        parent::__construct();

        if (is_null(static::$conn)) static::$conn = self::getConnection();
    }

    final public static function getConnection()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');

        if (null === ($resource = $bootstrap->getPluginResource('Mongo'))) {
            $bootstrap->registerPluginResource('Mongo');
            $resource = $bootstrap->getPluginResource('Mongo');
            $resource->init();
        }

        return $resource->getConnection();
    }
}
