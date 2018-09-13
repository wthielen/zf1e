<?php
/**
 * A base model class for classes which become Mongo documents.
 * @method static aggregate(array $args)
 * @method static findOne(array $args)
 * @method static findAndModify(array $args)
 * @method static remove(array $args)
 * @method static drop(array $args)
 * @method static distinct($field, array $args)
 */
class ZFE_Model_Mongo extends ZFE_Model_Base
{
    const COMMAND_FIND_ONE = 'findOne';
    const COMMAND_FIND_AND_MODIFY = 'findAndModify';
    const COMMAND_REMOVE = 'remove';
    const COMMAND_DROP = 'drop';
    const COMMAND_AGGREGATE = 'aggregate';
    const COMMAND_DISTINCT = 'distinct';

    /**
     * @var string
     */
    protected $_id = null;

    /**
     * @var bool
     */
    protected $_isPersistable = true;

    /**
     * @var ZFE_Resource_Mongo
     */
    protected static $resource;

    /**
     * @var MongoDB
     */
    protected static $db;

    /**
     * @var string
     */
    protected static $collection;

    /**
     * Tells PHP which field to use as the identifier field.
     * To keep it simple, this mini-ORM does not support compound
     * primary keys.
     *
     * The process cache is to quickly refer to earlier loaded entities
     * when referring to them with their identifiers.
     * @param string
     */
    protected static $_identifierField = 'id';

    /**
     * @var array
     */
    protected static $_cache;

    /**
     * A process cache for lazily loaded reference objects
     * @var array
     */
    static protected $_refCache;

    /**
     * Tracking what has been changed
     *
     * This is used by the save function to call on*Updated() functions.
     * @var array
     */
    protected $_changedFields = array();

    /**
     * Allowed command to be executed
     * @var array
     */
    protected static $allowedCommands = [
        self::COMMAND_FIND_ONE,
        self::COMMAND_FIND_AND_MODIFY,
        self::COMMAND_REMOVE,
        self::COMMAND_DROP,
        self::COMMAND_AGGREGATE,
        self::COMMAND_DISTINCT,
    ];

    /**
     * Mongo constructor, that calls the getDatabase() function, which in
     * turn initializes the Mongo application plugin resource.
     */
    public function __construct()
    {
        parent::__construct();

        static::$_refCache = array();
        static::getDatabase();
    }

    /**
     * Do some conversions for some types
     * @param string $key
     * @param mixed $val
     */
    public function __set($key, $val)
    {
        // If it is a Mongo entity, convert it to its reference
        $val = $this->normalizeEntity($val);

        // If it is an array of Mongo entities or DateTimes
        if (is_array($val)) {
            foreach ($val as &$entity) {
                $entity = $this->normalizeEntity($entity);
            }
        }

        $doCompare = !in_array($this->_status, array(static::STATUS_INITIALIZING, static::STATUS_IMPORT));

        // Do not use $this->$key here because $val is already translated so we don't need
        // back-translate from $this::__get
        if ($doCompare) {
            $oldValue = parent::__get($key);
        }
        parent::__set($key, $val);

        if ($doCompare && $oldValue !== $val) {
            if (!isset($this->_changedFields[$key])) {
                $this->_changedFields[$key] = array();
            }
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
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $val = parent::__get($key);

        if ($val === null && !isset($this->_data[$key])) {
            return null;
        }

        if (MongoDBRef::isRef($val)) {
            $objectId = (string) $val['$id'];
            if (!isset(static::$_refCache[$objectId])) {
                static::$_refCache[$objectId] = static::getObject($val);
            }

            return static::$_refCache[$objectId];
        } elseif ($val instanceof MongoDate) {
            $val = self::getDate($val);
        }

        return $val;
    }

    /**
     * Simulates a sequence generator as found in Oracle, and
     * MySQL's AUTO_INCREMENT.
     *
     * CAVEAT: when you import data into MongoDB, make sure the
     * ID sequence is set for that collection in your import script!
     *
     * @return mixed
     */
    public static function getNextId()
    {
        $sequenceCollection = static::getDatabase()->sequences;

        $nextIdRecord = $sequenceCollection->findAndModify(
            array(),
            array(
                '$inc' => array(
                    static::$collection => 1
                ),
            ),
            array(
                static::$collection => 1,
            ),
            array(
                'upsert' => true,
                'new' => true,
            )
        );

        return $nextIdRecord[static::$collection];
    }

    /**
     * Gets the maximum value of a field
     *
     * @param string $field
     * @param array $filter
     * @return mixed
     */
    public static function getMaximum($field, $filter = array())
    {
        $pipeline = array();
        if (!empty($filter)) {
            $pipeline[] = array('$match' => $filter);
        }

        $pipeline[] = array(
            '$group' => array(
                '_id' => '',
                'max' => array(
                    '$max' => '$' . $field,
                )
            )
        );

        $max = static::aggregate($pipeline, ['cursor' => true]);
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
     *
     * @param array $keys
     * @param int $levels
     * @return array
     */
    public function toArray($keys = null, $levels = 0)
    {
        if (empty($keys)) {
            $keys = array_keys($this->_data);
        }

        $ret = array();
        foreach ($keys as $key) {
            $val = isset($this->_data[$key]) ? $this->getTranslation($key) : $this->$key;

            if ($levels > 0) {
                if ($val instanceof ZFE_Model_Mongo) {
                    $val = $val->toArray(null, $levels - 1);
                }

                if (is_array($val)) {
                    foreach ($val as &$v) {
                        if ($v instanceof ZFE_Model_Mongo) {
                            $v = $v->toArray(null, $levels - 1);
                        }
                    }
                }
            }

            $ret[$key] = $val;
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function toSubdocument()
    {
        $ret = $this->_data;
        foreach ($ret as $key => $val) {
            $ret[$key] = $this->normalizeEntity($val);
        }

        return $ret;
    }

    /**
     * @param MongoDate $dt
     * @return DateTime
     */
    final public static function getDate(MongoDate $dt)
    {
        $val = new DateTime('@' . $dt->sec);
        $val->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        return $val;
    }

    /**
     * Get one Mongo object from reference
     * @param array $reference
     * @return array|null|ZFE_Model_Mongo
     * @throws ZFE_Model_Mongo_Exception
     */
    public static function getObject($reference)
    {
        $obj = $reference;
        if (MongoDBRef::isRef($reference)) {
            /** @var ZFE_Model_Mongo $cls */
            $cls = self::getResource()->getClass($reference['$ref']);
            if (!class_exists($cls)) {
                throw new ZFE_Model_Mongo_Exception(
                    'There is no model for the referred entity \'' . $reference['$ref'] . '\'.
                    Consider creating $cls or add a class mapping in resources.mongo.mapping[].'
                );
            }

            $obj = MongoDBRef::get(static::getDatabase(), $reference);
            if ($obj !== null) {
                $obj = $cls::map($obj);
            }
        }

        return $obj;
    }

    /**
     * @param array $references
     * @return array
     */
    public static function getObjects(array $references)
    {
        $objects = [];
        if (count($references)) {
            $query = [
                'query' => [
                    '_id' => $references,
                ],
            ];

            $result = static::find($query);
            $objects = array_values($result['result']);
        }

        return $objects;
    }

    /**
     * @param string $key
     * @param mixed $val
     * @param string $lang
     * @throws Exception
     */
    public function setTranslation($key, $val, $lang)
    {
        $val = $this->normalizeEntity($val);
        parent::setTranslation($key, $val, $lang);
    }

    /**
     * @param string $key
     * @param string|null $lang
     * @return array|null|ZFE_Model_Mongo
     * @throws ZFE_Model_Mongo_Exception
     */
    public function getTranslation($key, $lang = null)
    {
        $translation = parent::getTranslation($key, $lang);
        return static::getObject($translation);
    }

    /**
     * Gets the Mongo collection corresponding to this model
     *
     * @return MongoCollection
     * @throws ZFE_Model_Mongo_Exception
     */
    public static function getCollection()
    {
        if (static::$collection === null) {
            throw new ZFE_Model_Mongo_Exception('Please specify the collection name: protected static $collection');
        }

        return static::getDatabase()->{static::$collection};
    }

    /**
     * @return MongoGridFS
     */
    final public static function getGridFS()
    {
        return static::getDatabase()->getGridFS(static::$collection);
    }

    /**
     * Registers the database adapter in this model.
     *
     * Because the constructor calls this function, nothing needs to be done in the
     * application's bootstrap. Just create a Mongo document object :)
     *
     * @return MongoDB
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
     *
     * @param string $code
     * @param array $args
     * @return mixed
     * @throws ZFE_Model_Mongo_Exception
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
     *
     * @return ZFE_Resource_Mongo
     */
    final public static function getResource()
    {
        if (null === self::$resource) {
            self::$resource = ZFE_Environment::getResource('Mongo');
        }

        return self::$resource;
    }

    /**
     * Forwards some function calls to the MongoCollection functions
     *
     * @param string $name
     * @param array|mixed $args
     * @throws ZFE_Model_Mongo_Exception
     * @return mixed
     */
    public static function __callStatic($name, $args)
    {
        if (!in_array($name, static::$allowedCommands)) {
            throw new ZFE_Model_Mongo_Exception("Unknown static function $name");
        }

        $ret = call_user_func_array(array(static::getCollection(), $name), $args);

        // Do some conversion if needed
        if ($ret) {
            switch ($name) {
                case static::COMMAND_FIND_ONE:
                    $ret = static::map($ret);
                    break;
                case static::COMMAND_AGGREGATE:
                    if ($ret['ok'] == 0) {
                        throw new ZFE_Model_Mongo_Exception($ret['errmsg'], $ret['code']);
                    }
                    $ret = $ret['result'];
                    break;
                case static::COMMAND_DISTINCT:
                    $result = $references = $referenceIds = [];
                    foreach ($ret as $val) {
                        if (MongoDBRef::isRef($val)) {
                            $referenceIds[] = $val['$id'];
                            /** @var ZFE_Model_Mongo $class */
                            $class = static::getResource()->getClass($val['$ref']);
                        } else {
                            $result[] = $val;
                        }
                    }

                    if (isset($class)) {
                        $references = $class::getObjects($referenceIds);
                    }

                    $ret = array_merge($result, $references);
                    break;
            }
        }

        return $ret;
    }

    /**
     * Gets an entry from the database, given the identifier(s) and the field name
     *
     * If no field name is given, the stored _identifierField is used.
     *
     * @param string $id
     * @param string $field
     * @return array|null
     */
    public static function get($id, $field = null)
    {
        $field = is_null($field) ? static::$_identifierField : $field;
        $class = get_called_class();
        if (!isset(self::$_cache[$class])) {
            self::$_cache[$class] = array();
        }

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

                foreach ($fetched['result'] as $entry) {
                    self::$_cache[$class][$entry->getIdentifier()] = $entry;
                }
            }

            return array_intersect_key(self::$_cache[$class], array_flip($id));
        }

        // Single parameter case
        // Simply fetch it from the database and store it in the cache if
        // it is not already stored in the cache, and then return from cache.
        if (!isset(self::$_cache[$class][$id])) {
            $found = static::findOne(array($field => $id));
            if ($found) {
                self::$_cache[$class][$id] = $found;
            }
        }

        return isset(self::$_cache[$class][$id]) ? self::$_cache[$class][$id] : null;
    }

    /**
     * Returns the identifier of this entry using the
     * protected $_identifierField field
     *
     * @param null $field
     * @return string
     */
    public function getIdentifier($field = null)
    {
        $field = is_null($field) ? static::$_identifierField : $field;

        if (isset($this->_data[$field])) {
            return $this->_data[$field];
        }

        return $this->getMongoIdentifier();
    }

    /**
     * Returns the Mongo identifier
     * @return string?
     */
    public function getMongoIdentifier()
    {
        return $this->_id;
    }

    /**
     * Convert $query items for mongo. Eg. convert {'key': [values]} to {'key': '$in': [values]} and
     * convert objects to their mongo ID
     * @param array $query ACQ mongo query to be converted
     * @return array $query PHP mongo ready query
     */
    protected static function _convertQuery($query)
    {
        // Replace ZFE_Model_Mongo instances by their references
        $replaceWithReference = function(&$val) {
            $val = $val instanceof ZFE_Model_Mongo ? $val->getReference() : $val;
        };

        array_walk($query, function(&$val, $key) use ($replaceWithReference) {
            if ($key[0] == '$') {
                return;
            }

            // Special case to take MongoIds out of references
            if ($key == '_id' && is_array($val)) {
                // If it is a single reference, take out the ID and continue
                if (MongoDBRef::isRef($val)) {
                    $val = $val['$id'];
                    return;
                }

                // Create an $in operation with an array of IDs
                $val = array(
                    '$in' => array_map(
                        function($ref) {
                            return MongoDBRef::isRef($ref) ? $ref['$id'] : $ref;
                        },
                        $val
                    )
                );

                return;
            }

            if (is_array($val)) {
                $keys = array_keys($val);
                $mongoOperators = array_reduce(
                    $keys,
                    function($u, $v) {
                        return $u || $v[0] == '$';
                    },
                    false
                );

                if (!$mongoOperators) {
                    array_walk($val, $replaceWithReference);

                    // MB UPDATE: I think this has to be {'$in': ["en", "ja"... rather than {'$in': {"1":"ja","2":"fr",..
                    // which previously just '$in' => $val was creating, wrong array for $in
                    // anyway, putting a note for now encase it breaks anything :)
                    $val = array('$in' => array_values($val));
                    // $val = array('$in' => $val);
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
     *
     * @param array $args
     * @return array
     * @throws ZFE_Model_Mongo_Exception
     */
    public static function find($args = array())
    {
        $default = array('query' => array(), 'fields' => array());
        $args = array_merge($default, $args);

        $args['query'] = static::_convertQuery($args['query']);

        // Add projection keys for $meta sort entries
        if (isset($args['sort']) && is_array($args['sort'])) {
            foreach ($args['sort'] as $fld => $entry) {
                if (is_array($entry) && isset($entry['$meta'])) {
                    $args['fields'][$fld] = $entry;
                }
            }
        }

        $cursor = static::getCollection()->find($args['query'], $args['fields']);
        //$count = $cursor->count();

        if (isset($args['sort']) && is_array($args['sort'])) {
            // Convert 'asc' and 'desc' to 1 and -1
            foreach ($args['sort'] as &$val) {
                // Skip metadata sorting
                if (is_array($val)) {
                    continue;
                }

                $val = strtolower($val);
                if ($val == 'asc') {
                    $val = 1;
                } elseif ($val == 'desc') {
                    $val = -1;
                } else {
                    $val = gmp_sign($val);
                }
            }
            $cursor->sort($args['sort']);
        }

        // Apply pagination
        if (isset($args['offset']) || isset($args['limit'])) {
            $offset = @intval($args['offset']);
            $limit = @intval($args['limit']);

            if ($offset > 0) {
                $cursor->skip($offset);
            }
            if ($limit > 0) {
                $cursor->limit($limit);
            }
        }

        // Do not remove the 'result' entry. It is important for the findPaginated function
        // If removed here, fix findPaginated to have a 'result' array entry
        $ret = array(
            'result' => array_map(array(get_called_class(), 'map'), iterator_to_array($cursor)),
            // 'total' => $count
        );

        return $ret;
    }

    /**
     * @param array $query
     * @return int
     * @throws ZFE_Model_Mongo_Exception
     */
    public static function count($query = array())
    {
        $query = static::_convertQuery($query);
        return static::getCollection()->count($query);
    }

    /**
     * A wrapper function to fetch records, using a paginator to determine
     * the offset and limit. It will re-page and re-fetch if the currently
     * set page number is out of bounds.
     *
     * @param ZFE_Util_Paginator $paginator
     * @param array $args
     * @return array
     */
    public static function findPaginated($paginator, $args = array())
    {
        $args['offset'] = $paginator->getOffset();
        $args['limit'] = $paginator->getItems();

        if (!isset($args['query'])) {
            $args['query'] = array();
        }

        $count = static::count($args['query']);
        $paginator->setTotal($count);

        $args['offset'] = $paginator->getOffset();

        $ret = static::find($args);
        $ret['total'] = $count;

        return $ret;
    }

    /**
     * Saves the data member into the Mongo collection
     * @throws ZFE_Model_Mongo_Exception
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
        foreach ($data as $key => $val) {
            if (is_null($val)) {
                unset($data[$key]);
            }
        }

        $collection->save($data);

        $this->_id = $data['_id'];
        unset($data['_id']);
        $this->_data = $data;

        // Remove from cache after saving
        $class = get_called_class();
        unset(self::$_cache[$class][(string) $this->getIdentifier()]);
        $objectId = (string) $this->_id;
        if (isset(static::$_refCache[$objectId])) {
            unset(static::$_refCache[$objectId]);
        }

        // Run on*Updated functions on changed fields and clear it
        foreach ($this->_changedFields as $field => $oldValues) {
            $fn = ZFE_Util_String::toCamelCase('on-' . $field . '-updated');
            if (method_exists($this, $fn)) {
                $this->$fn($oldValues);
            }
        }

        $this->_changedFields = array();
    }

    /**
     * Removes the data associated with this object, from the Mongo collection
     */
    public function delete()
    {
        static::getCollection()->remove(array('_id' => $this->_id), array('justOne' => true));
    }

    /**
     * Creates a reference of this instance to be used in another instance
     *
     * If there is no _id entry in this instance, we save this instance into
     * MongoDB so that we get an _id identifier.
     *
     * @return array
     * @throws ZFE_Model_Mongo_Exception
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
     *
     * @param array $data
     * @return ZFE_Model_Mongo
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

    /**
     * Normalize Mongo entity
     *
     * @param mixed $entity
     * @return mixed|MongoDBRef|MongoDate
     */
    protected function normalizeEntity($entity)
    {
        // If it is a Mongo entity, convert it to its reference
        if ($entity instanceof ZFE_Model_Mongo) {
            $entity = $entity->getReference();
        }

        // If it is a DateTime, convert it to MongoDate
        if ($entity instanceof DateTime) {
            $entity = new MongoDate($entity->getTimestamp());
        }

        return $entity;
    }
}
