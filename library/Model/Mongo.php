<?php

/**
 * A base model class for classes which become Mongo documents.
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    protected $_id;
    protected $_isPersistable = true;

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
     * Tracking what has been changed
     *
     * This is used by the save function to call on*Updated() functions.
     */
    protected $_changedFields = array();

    /**
     * Mongo constructor, that calls the getDatabase() function, which in
     * turn initializes the Mongo application plugin resource.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_refCache = array();

        static::getDatabase();
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

        $doCompare = !in_array($this->_status, array(self::STATUS_INITIALIZING, self::STATUS_IMPORT));

        // Do not use $this->$key here because $val is already translated so we don't need
        // back-translate from $this::__get
        if ($doCompare) $oldValue = parent::__get($key);
        parent::__set($key, $val);

        if ($doCompare && $oldValue !== $val) {
            if (!isset($this->_changedFields[$key])) $this->_changedFields[$key] = array();
            $this->_changedFields[$key][] = $oldValue;
        }
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
        $val = parent::__get($key);

        if (is_null($val) && !isset($this->_data[$key])) return null;

        if (MongoDBRef::isRef($val)) {
            if (isset($this->_refCache[$key])) return $this->_refCache[$key];

            $obj = static::getObject($val);

            $this->_refCache[$key] = $obj;
            return $obj;
        }

        if ($val instanceof MongoDate) $val = self::getDate($val);

        return $val;
    }

    /**
     * Simulates a sequence generator as found in Oracle, and
     * MySQL's AUTO_INCREMENT.
     *
     * CAVEAT: when you import data into MongoDB, make sure the
     * ID sequence is set for that collection in your import script!
     */
    public static function getNextId()
    {
        $sequenceCollection = static::getDatabase()->sequences;

        $nextIdRecord = $sequenceCollection->findAndModify(array(),
            array(
                '$inc' => array(static::$collection => 1)
            ),
            array(static::$collection => 1),
            array('upsert' => true, 'new' => true)
        );

        return $nextIdRecord[static::$collection];
    }

    /**
     * Gets the maximum value of a field
     */
    public static function getMaximum($field, $filter = array())
    {
        $pipeline = array();
        if (!empty($filter)) $pipeline[] = array('$match' => $filter);

        $pipeline[] = array('$group' => array(
            '_id' => "",
            'max' => array('$max' => '$' . $field)
        ));

        $max = static::aggregate($pipeline);
        $value = count($max) ? $max[0]['max'] : null;

        return $value;
    }

    /**
     * Converts the object into an array.
     *
     * It will convert sub-objects as well, up to $levels levels.
     *
     * Do not allow for full sub-object conversion because this will likely end up
     * in a deadlock or a memory overflow. Best to pass a $levels argument to keep
     * it controlled.
     */
    public function toArray($keys = null, $levels = 0)
    {
        if (is_null($keys)) $keys = array_keys($this->_data);

        $ret = array();
        foreach($keys as $key) {
            $val = isset($this->_data[$key]) ? $this->getTranslation($key) : $this->$key;

            if ($levels > 0) {
                if ($val instanceof ZFE_Model_Mongo) $val = $val->toArray(null, $levels - 1);

                if (is_array($val)) {
                    foreach($val as &$v) {
                        if ($v instanceof ZFE_Model_Mongo) $v = $v->toArray(null, $levels - 1);
                    }
                }
            }

            $ret[$key] = $val;
        }

        return $ret;
    }

    public function toSubdocument()
    {
        $ret = $this->_data;

        foreach($ret as $key => $val) {
            if ($val instanceof ZFE_Model_Mongo) $ret[$key] = $val->getReference();
            if ($val instanceof DateTime) $ret[$key] = new MongoDate($val->getTimestamp());
        }

        return $ret;
    }

    final public static function getDate(MongoDate $dt) 
    {
        $val = new DateTime('@' . $dt->sec);
        $val->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        return $val;
    }

    public static function getObject($ref)
    {
        $obj = $ref;
        if (MongoDBRef::isRef($ref)) {
            $cls = self::$resource->getClass($ref['$ref']);
            if (!class_exists($cls)) {
                throw new ZFE_Model_Mongo_Exception(
                    "There is no model for the referred entity '" . $ref['$ref'] . "'.
                    Consider creating $cls or add a class mapping in resources.mongo.mapping[]."
                );
            }

            $obj = MongoDBRef::get(static::getDatabase(), $ref);
            $obj = is_null($obj) ? $obj : $cls::map($obj);
        }

        return $obj;
    }

    public function setTranslation($key, $val, $lang)
    {
        // If it is a Mongo entity, convert it to its reference
        if ($val instanceof ZFE_Model_Mongo) {
            $val = $val->getReference();
        }

        // If it is a DateTime, convert it to MongoDate
        if ($val instanceof DateTime) {
            $val = new MongoDate($val->getTimestamp());
        }

        parent::setTranslation($key, $val, $lang);
    }

    public function getTranslation($key, $lang = null)
    {
        $translation = parent::getTranslation($key, $lang);

        return static::getObject($translation);
    }

    /**
     * Gets the Mongo collection corresponding to this model
     */
    public static function getCollection()
    {
        if (is_null(static::$collection)) {
            throw new ZFE_Model_Mongo_Exception("Please specify the collection name: protected static \$collection");
        }

        return static::getDatabase()->{static::$collection};
    }

    final public static function getGridFS()
    {
        return static::getDatabase()->getGridFS(static::$collection);
    }

    /**
     * Registers the database adapter in this model.
     *
     * Because the constructor calls this function, nothing needs to be done in the
     * application's bootstrap. Just create a Mongo document object :)
     */
    final public static function getDatabase()
    {
        if (null === static::$db) {
            static::$db = static::getResource()->getDatabase();
        }

        return static::$db;
    }

    /**
     * A wrapper around execute() that will throw a PHP exception when something goes
     * wrong here.
     */
    final public static function execute($code, $args = array())
    {
        $db = static::getDatabase();

        $result = $db->execute($code, $args);
        if ($result['ok'] == 0) {
            throw new ZFE_Model_Mongo_Exception($result['errmsg'], $result['code']);
        }

        return $result['retval'];
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
            self::$resource = $bootstrap->getPluginResource('Mongo');
        }

        return self::$resource;
    }

    /**
     * Forwards some function calls to the MongoCollection functions
     */
    public static function __callStatic($name, $args)
    {
        $whitelist = array(
            'findOne',
            'findAndModify',
            'remove',
            'drop',
            'aggregate',
            'distinct'
        );

        if (!in_array($name, $whitelist)) {
            throw new ZFE_Model_Mongo_Exception("Unknown static function $name");
        }

        $ret = call_user_func_array(array(static::getCollection(), $name), $args);

        // Do some conversion if needed
        if ($ret) {
            switch($name) {
            case 'findOne':
                $ret = static::map($ret);
                break;
            case 'aggregate':
                if ($ret['ok'] == 0) throw new ZFE_Model_Mongo_Exception($ret['errmsg'], $ret['code']);
                $ret = $ret['result'];
                break;
            }
        }

        return $ret;
    }

    /**
     * Gets an entry from the database, given the identifier(s) and the field name
     *
     * If no field name is given, the stored _identifierField is used.
     */
    public static function get($id, $field = null)
    {
        $field = is_null($field) ? static::$_identifierField : $field;
        $class = get_called_class();
        if (!isset(self::$_cache[$class])) self::$_cache[$class] = array();

        // Multiple parameters case:
        // Checks if there are any IDs not in the cache, which we need
        // to load from the database. If there are any, it fetches them using
        // one find() call, and stores the objects in the process cache.
        // Then it returns a slice of the cache with the requested IDs.
        //
        // TODO $field is not passed to getIdentifier because here it is "model.id"
        // for another project, which is the field in Mongo, but in _data it is "id".
        // Not sure what to do here.
        if (is_array($id)) {
            $toFetch = array_values(array_diff($id, array_keys(self::$_cache[$class])));
            if (count($toFetch)) {
                $fetched = static::find(array('query' => array($field => $toFetch)));

                foreach($fetched['result'] as $entry) {
                    self::$_cache[$class][$entry->getIdentifier()] = $entry;
                }
            }

            return array_intersect_key(self::$_cache[$class], array_flip($id));
        }

        // Single parameter case
        // Simply fetch it from the database and store it in the cache if
        // it is not already stored in the cache, and then return from cache.
        if (!isset(self::$_cache[$class][$id])) {
            self::$_cache[$class][$id] = static::findOne(array('query' => array($field => $id)));
        }

        return self::$_cache[$class][$id];
    }

    /**
     * Returns the identifier of this entry using the
     * protected $_identifierField field
     */
    public function getIdentifier($field = null)
    {
        $field = is_null($field) ? static::$_identifierField : $field;

        if (isset($this->_data[$field])) {
            return $this->_data[$field];
        }

        return null;
    }

    /**
     * Returns the Mongo identifier
     */
    public function getMongoIdentifier()
    {
        return $this->_id;
    }

    protected static function _convertQuery($query)
    {
        // Replace ZFE_Model_Mongo instances by their references
        $replaceWithReference = function(&$val) {
            $val = $val instanceof ZFE_Model_Mongo ? $val->getReference() : $val;
        };

        array_walk($query, function(&$val, $key) use($replaceWithReference) {
            if ($key[0] == '$') return;

            // Special case to take MongoIds out of references
            if ($key == '_id' && is_array($val)) {
                // If it is a single reference, take out the ID and continue
                if (MongoDBRef::isRef($val)) {
                    $val = $val['$id'];
                    return;
                }

                // Create an $in operation with an array of IDs
                $val = array('$in' => array_map(function($ref) {
                    return MongoDBRef::isRef($ref) ? $ref['$id'] : $ref;
                }, $val));

                return;
            }

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

        return $query;
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

        $args['query'] = static::_convertQuery($args['query']);

        // Add projection keys for $meta sort entries
        if (isset($args['sort']) && is_array($args['sort'])) {
            foreach($args['sort'] as $fld => $entry) {
                if (is_array($entry) && isset($entry['$meta'])) {
                    $args['fields'][$fld] = $entry;
                }
            }
        }

        $cursor = static::getCollection()->find($args['query'], $args['fields']);
        //$count = $cursor->count();

        if (isset($args['sort']) && is_array($args['sort'])) {
            // Convert 'asc' and 'desc' to 1 and -1
            foreach($args['sort'] as &$val) {
                // Skip metadata sorting
                if (is_array($val)) continue;

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
            'result' => array_map(array(get_called_class(), 'map'), iterator_to_array($cursor)),
            // 'total' => $count
        );

        return $ret;
    }

    public static function count($query)
    {
        $query = static::_convertQuery($query);
        return static::getCollection()->count($query);
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

        $count = static::count($args['query']);
        $paginator->setTotal($count);

        $args['offset'] = $paginator->getOffset();

        $ret = static::find($args);
        $ret['total'] = $count;

        return $ret;
    }

    /**
     * Saves the data member into the Mongo collection
     */
    public function save()
    {
        if (!$this->_isPersistable) {
            throw new ZFE_Model_Mongo_Exception("Can not save a non-persistable instance");
        }

        $collection = static::getCollection();

        $data = $this->_data;

        if (isset($this->_id)) {
            $data = array_merge(
                array('_id' => $this->_id),
                $data
            );
        }

        // Remove from model if value is null
        foreach($data as $key => $val) {
            if (is_null($val)) unset($data[$key]);
        }

        $collection->save($data);

        $this->_id = $data['_id'];
        unset($data['_id']);
        $this->_data = $data;

        // Remove from cache after saving
        $class = get_called_class();
        unset(self::$_cache[$class][$this->_data[static::$_identifierField]]);

        // Run on*Updated functions on changed fields and clear it
        foreach($this->_changedFields as $fld => $oldValues) {
            $fn = ZFE_Util_String::toCamelCase("on-" . $fld . "-updated");
            if (method_exists($this, $fn)) $this->$fn($oldValues);
        }
        $this->_changedFields = array();
    }

    /**
     * Removes the data associated with this object, from the Mongo collection
     */
    public function delete()
    {
        static::remove(array('_id' => $this->_id), array('justOne' => true));
    }

    /**
     * Creates a reference of this instance to be used in another instance
     *
     * If there is no _id entry in this instance, we save this instance into
     * MongoDB so that we get an _id identifier.
     */
    public function getReference()
    {
        if (!isset($this->_id)) {
            $this->save();
        }

        return MongoDBRef::create(static::$collection, $this->_id);
    }

    /**
     * Maps data from the MongoDB database into an object instance
     */
    public static function map($data)
    {
        $obj = new static();

        if (isset($data['_id'])) {
            $obj->_id = $data['_id'];
            unset($data['_id']);
        }

        $obj->init($data);

        return $obj;
    }
}
