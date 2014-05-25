<?php

/**
 * A base model class for classes which become Mongo documents.
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    protected static $db;
    protected static $collection;

    /**
     * An array for mapping collection names to PHP class names
     * This can be configured in the application's configuration file
     * for not-so-obvious mappings.
     */
    protected static $mapping;

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
     * If the passed value is a MongoDB entity, convert it into
     * its reference
     */
    public function __set($key, $val)
    {
        if ($val instanceof ZFE_Model_Mongo) {
            $val = $val->getReference();
        }

        parent::__set($key, $val);
    }

    /**
     * If the accessed data entry is a MongoDB reference, fetch the
     * reference data and turn it into an object of the reference data
     * class.
     *
     * By default, it will create an object with the class name based on the
     * reference collection, but if it is mentioned in the mapping configuration,
     * it will use the mapping's setting instead. If the class does not exist,
     * an explanatory exception will be thrown.
     */
    public function __get($key)
    {
        if (MongoDBRef::isRef($this->_data[$key])) {
            $ref = $this->_data[$key]['$ref'];
            $ref = isset(self::$mapping[$ref]) ? self::$mapping[$ref] : ucfirst($ref);

            $prefix = ZFE_Environment::getResourcePrefix('model');

            $cls = $prefix . '_' . $ref;
            if (!class_exists($cls)) {
                throw new ZFE_Model_Mongo_Exception(
                    "There is no model for the referred entity '" . $this->_data[$key]['$ref'] . "'.
                    Consider creating $cls or add a class mapping in resources.mongo.mapping[]."
                );
            }

            $val = new $cls();
            $val->init(MongoDBRef::get(self::getDatabase(), $this->_data[$key]));

            return $val;
        }

        return parent::__get($key);
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

            $options = $resource->getOptions();
            self::$mapping = isset($options['mapping']) ? $options['mapping'] : array();
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
     * Creates a reference of this instance to be used in another instance
     *
     * If there is no _id entry in this instance, we save this instance into
     * MongoDB so that we get an _id identifier.
     */
    public function getReference()
    {
        if (!isset($this->_data['_id'])) {
            $this->save();
        }

        return MongoDBRef::create(static::$collection, $this->_data['_id']);
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
