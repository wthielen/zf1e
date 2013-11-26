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

        static::getDatabase();
    }

    /**
     * Gets the Mongo collection corresponding to this model
     */
    final public static function getCollection()
    {
        if (is_null(static::$collection)) {
            throw new ZFE_Model_Mongo_Exception("Please specify the collection name: protected static \$collection");
        }

        return static::getDatabase()->{static::$collection};
    }

    /**
     * Registers the MongoDB application plugin resource and initializes it, when a
     * connection is requested. It stores the database as a static adapter in the model.
     *
     * Because the constructor calls this function, nothing needs to be done in the
     * application's bootstrap. Just create a Mongo document object :)
     *
     * TODO See if we need static::$db or self::$db, since we only need one $db for all
     * Mongo objects.
     */
    final public static function getDatabase()
    {
        if (null === static::$db) {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            if (null === ($resource = $bootstrap->getPluginResource('Mongo'))) {
                $bootstrap->registerPluginResource('Mongo');
                $resource = $bootstrap->getPluginResource('Mongo');
                $resource->init();
            }

            static::$db = $resource->getDatabase();
        }

        return static::$db;
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

        if ('MongoCursor' === get_class($ret)) $ret = self::_mapCollection($ret);

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
     * Maps results from the Mongo cursor into object instances,
     * puts them in an array and returns this one
     */
    private static function _mapCollection(MongoCursor $cursor)
    {
        $ret = array();

        $cursor->reset();
        foreach($cursor as $item) {
            $entry = new static();
            $entry->init($item);
            $ret[] = $entry;
        }

        return $ret;
    }
}
