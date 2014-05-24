<?php

/**
 * A base model class for classes which become Mongo documents.
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    protected static $db;
    protected static $collection;

    /**
     * Mongo constructor, that calls the getDatabase() function, which in
     * turn initializes the Mongo application plugin resource.
     */
    public function __construct()
    {
        parent::__construct();

        self::getDatabase();
    }

    /**
     * Gets the Mongo collection corresponding to this model
     */
    final public static function getCollection()
    {
        if (is_null(static::$collection)) {
            throw new ZFE_Model_Mongo_Exception("Please specify the collection name: protected static \$collection");
        }

        return self::getDatabase()->{static::$collection};
    }

    /**
     * Registers the MongoDB application plugin resource and initializes it, when a
     * connection is requested. It stores the database as a static adapter in the model.
     *
     * Because the constructor calls this function, nothing needs to be done in the
     * application's bootstrap. Just create a Mongo document object :)
     */
    final public static function getDatabase()
    {
        if (null === self::$db) {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            if (null === ($resource = $bootstrap->getPluginResource('Mongo'))) {
                $bootstrap->registerPluginResource('Mongo');
                $resource = $bootstrap->getPluginResource('Mongo');
                $resource->init();
            }

            self::$db = $resource->getDatabase();
        }

        return self::$db;
    }

    /**
     * Forwards some function calls to the MongoCollection functions
     */
    public static function __callStatic($name, $args)
    {
        $whitelist = array(
            'count',
            'find',
            'findOne',
            'remove'
        );

        if (!in_array($name, $whitelist)) {
            throw new ZFE_Model_Mongo_Exception("Unknown static function $name");
        }

        $ret = call_user_func_array(array(self::getCollection(), $name), $args);

        // Do some conversion if needed
        switch($name) {
        case 'find':
            $ret = array_map(array(get_called_class(), '_map'), iterator_to_array($ret));
            break;
        case 'findOne':
            $ret = self::_map($ret);
            break;
        }

        return $ret;
    }

    /**
     * Saves the data member into the Mongo collection
     */
    public function save()
    {
        $collection = self::getCollection();
        $collection->save($this->_data);
    }

    /**
     * Maps data from the MongoDB database into an object instance
     */
    private static function _map(array $data)
    {
        $obj = new static();
        $obj->init($data);
        return $obj;
    }
}
