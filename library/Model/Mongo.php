<?php

/**
 * A base model class for classes which become Mongo documents.
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    protected static $db;
    protected static $collection;

    /**
     * A cache for lazily loaded reference objects
     */
    private $_cache;

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

        $this->_cache = array();

        self::getDatabase();
    }

    /**
     * Do some conversions for some types
     */
    public function __set($key, $val)
    {
        // If it is a Mongo entity, convert it to its reference
        if ($val instanceof ZFE_Model_Mongo) {
            $val = $val->getReference();
        }

        // If it is a DateTime, convert it to MongoDate
        if ($val instanceof DateTime) {
            $val = new MongoDate($val->getTimestamp());
        }

        // If it is an array of Mongo entities or DateTimes
        if (is_array($val)) {
            $_val = array();
            foreach($val as $i => $v) {
                if ($v instanceof ZFE_Model_Mongo) $v = $v->getReference();
                if ($v instanceof DateTime) $v = new MongoDate($v->getTimestamp());
                $_val[$i] = $v;
            }
            $val = $_val;
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
     *
     * Other conversions: MongoDate to DateTime
     */
    public function __get($key)
    {
        if (!isset($this->_data[$key])) return null;

        if (MongoDBRef::isRef($this->_data[$key])) {
            if (isset($this->_cache[$key])) return $this->_cache[$key];

            $ref = $this->_data[$key]['$ref'];

            // TODO app prefix? model prefix?
            $cls = 'Model_' . ucfirst($ref);
            if (isset(self::$mapping[$ref])) {
                $cls = self::$mapping[$ref];
            }

            if (!class_exists($cls)) {
                throw new ZFE_Model_Mongo_Exception(
                    "There is no model for the referred entity '" . $this->_data[$key]['$ref'] . "'.
                    Consider creating $cls or add a class mapping in resources.mongo.mapping[]."
                );
            }

            $val = new $cls();
            $val->init(MongoDBRef::get(self::getDatabase(), $this->_data[$key]));

            $this->_cache[$key] = $val;

            return $val;
        }

        if ($this->_data[$key] instanceof MongoDate) {
            $val = new DateTime('@' . $this->_data[$key]->sec);
            $val->setTimeZone(new DateTimeZone(date_default_timezone_get()));
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
            'findOne',
            'remove'
        );

        if (!in_array($name, $whitelist)) {
            throw new ZFE_Model_Mongo_Exception("Unknown static function $name");
        }

        $ret = call_user_func_array(array(self::getCollection(), $name), $args);

        // Do some conversion if needed
        if ($ret) {
            switch($name) {
            case 'findOne':
                $ret = self::_map($ret);
                break;
            }
        }

        return $ret;
    }

    /**
     * To be able to paginate results, I have taken out the 'find' call and made it
     * its own function. It returns an array containing the result set, and the
     * total number of results, useful for pagination
     */
    public static function find($args = array())
    {
        $default = array('query' => array(), 'fields' => array());
        $args = array_merge($default, $args);

        $cursor = self::getCollection()->find($args['query'], $args['fields']);
        $count = $cursor->count();

        if (isset($args['sort']) && is_array($args['sort'])) {
            // Convert 'asc' and 'desc' to 1 and -1
            foreach($args['sort'] as &$val) {
                $val = strtolower($val);
                if ($val == 'asc') {
                    $val = 1;
                } else if ($val == 'desc') {
                    $val = -1;
                } else {
                    $val = gmp_sign($val);
                }
            }
            $cursor->sort($args['sort']);
        }

        // Apply pagination
        if (isset($args['offset']) && is_scalar($args['offset'])) $cursor->skip($args['offset']);
        if (isset($args['limit']) && is_scalar($args['limit'])) $cursor->limit($args['limit']);

        return array(
            'result' => array_map(array(get_called_class(), '_map'), iterator_to_array($cursor)),
            'total' => $count
        );
    }

    /**
     * A wrapper function to fetch records, using a paginator to determine
     * the offset and limit. It will re-page and re-fetch if the currently
     * set page number is out of bounds.
     */
    public static function findPaginated($paginator, $args = array())
    {
        $args['offset'] = $paginator->getOffset();
        $args['limit'] = $paginator->getItems();

        $ret = static::find($args);
        if ($paginator->setTotal($ret['total'])) {
            $args['offset'] = $paginator->getOffset();
            $ret = static::find($args);
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
