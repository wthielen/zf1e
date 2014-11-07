<?php

/**
 * A base model class for classes which become Mongo documents.
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    protected static $resource;
    protected static $db;
    protected static $collection;

    /**
     * Tells PHP which field to use as the identifier field.
     * To keep it simple, this mini-ORM does not support compound
     * primary keys.
     *
     * The process cache is to quickly refer to earlier loaded entities
     * when referring to them with their identifiers.
     */
    protected static $_identifierField = 'id';
    protected static $_cache;

    /**
     * A process cache for lazily loaded reference objects
     */
    private $_refCache;

    /**
     * Mongo constructor, that calls the getDatabase() function, which in
     * turn initializes the Mongo application plugin resource.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_cache = array();
        $this->_refCache = array();

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
            if (isset($this->_refCache[$key])) return $this->_refCache[$key];

            $ref = $this->_data[$key]['$ref'];
            $cls = self::$resource->getClass($ref);
            if (!class_exists($cls)) {
                throw new ZFE_Model_Mongo_Exception(
                    "There is no model for the referred entity '" . $ref . "'.
                    Consider creating $cls or add a class mapping in resources.mongo.mapping[]."
                );
            }

            $val = new $cls();
            $val->init(MongoDBRef::get(self::getDatabase(), $this->_data[$key]));

            $this->_refCache[$key] = $val;
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
     * Registers the database adapter in this model.
     *
     * Because the constructor calls this function, nothing needs to be done in the
     * application's bootstrap. Just create a Mongo document object :)
     */
    final public static function getDatabase()
    {
        if (null === self::$db) {
            self::$db = self::getResource()->getDatabase();
        }

        return self::$db;
    }

    /**
     * Registers the MongoDB application plugin resource and initializes it, when a
     * connection is requested. It stores the plugin resource as a static entry in
     * the model.
     */
    final public static function getResource()
    {
        if (null === self::$resource) {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            if (null === ($resource = $bootstrap->getPluginResource('Mongo'))) {
                $bootstrap->registerPluginResource('Mongo');
                $resource = $bootstrap->getPluginResource('Mongo');
                $resource->init();
            }

            self::$resource = $resource;
        }

        return self::$resource;
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
     * Gets an entry from the database, given the identifier(s)
     */
    public static function get($id)
    {
        // Multiple parameters case:
        // Checks if there are any IDs not in the cache, which we need
        // to load from the database. If there are any, it fetches them using
        // one find() call, and stores the objects in the process cache.
        // Then it returns a slice of the cache with the requested IDs.
        if (is_array($id)) {
            $toFetch = array_diff($id, array_keys(self::$_cache));
            if (count($toFetch)) {
                $fetched = self::find(array('query' => array(
                    static::$_identifierField => $toFetch
                )));

                foreach($fetched['result'] as $entry) {
                    self::$_cache[$entry->getIdentifier()] = $entry;
                }
            }

            return array_intersect_key(self::$_cache, array_flip($id));
        }

        // Single parameter case
        // Simply fetch it from the database and store it in the cache if
        // it is not already stored in the cache, and then return from cache.
        if (!isset(self::$_cache[$id])) {
            self::$_cache[$id] = self::findOne(array(
                static::$_identifierField => $id
            ));
        }

        return self::$_cache[$id];
    }

    /**
     * Returns the identifier of this entry using the
     * protected $_identifierField field
     */
    public function getIdentifier()
    {
        if (isset($this->_data[static::$_identifierField])) {
            return $this->_data[static::$_identifierField];
        }

        return null;
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

        // Replace ZFE_Model_Mongo instances by their references
        $replaceWithReference = function(&$val) {
            $val = $val instanceof ZFE_Model_Mongo ? $val->getReference() : $val;
        };

        array_walk($args['query'], function(&$val, $key) use($replaceWithReference) {
            if ($key[0] == '$') return;

            // Create a $in operation for multiple arguments to a query field
            if (is_array($val)) {
                $keys = array_keys($val);
                $mongoOperators = array_reduce($keys, function($u, $v) {
                    return $u || $v[0] == '$';
                }, false);

                if (!$mongoOperators) {
                    array_walk($val, $replaceWithReference);
                    $val = array('$in' => $val);
                }
            } else {
                $replaceWithReference($val);
            }
        });

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
        if (isset($args['offset']) && isset($args['limit'])) {
            $offset = intval($args['offset']);
            $limit = intval($args['limit']);

            if ($offset > 0) $cursor->skip($offset);
            if ($limit > 0) $cursor->limit($limit);
        }

        $ret = array(
            'result' => array_map(array(get_called_class(), '_map'), iterator_to_array($cursor)),
            'total' => $count
        );

        return $ret;
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

        // Remove from cache after saving
        unset(self::$_cache[$this->_data[static::$_identifierField]]);
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
