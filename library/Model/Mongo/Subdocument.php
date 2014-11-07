<?php

/**
 * A subdocument class, to be able to make object instances
 * of subdocuments in the application and treat them like they
 * are their own entity.
 *
 * This does NOT extend ZFE_Model_Mongo, because find/save
 * operations on subdocuments need to be done via their parent
 * documents. This class is just an object representation of
 * MongoDB subdocuments.
 */
class ZFE_Model_Mongo_Subdocument extends ZFE_Model_Base
{
    /**
     * A process cache for lazily loaded reference objects
     */
    private $_refCache;

    /**
     * Subdocument constructor
     *
     * This class needs to know which database to work with, so 
     * it has a self::getDatabase() function which calls 
     * ZFE_Model_Mongo::getDatabase(). And this initializes the
     * Mongo resource.
     */
    public function __construct()
    {
        parent::__construct();

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

            $resource = ZFE_Model_Mongo::getResource();
            $ref = $this->_data[$key]['$ref'];
            $cls = $resource->getClass($ref);
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
     * A simple forwarding function that returns the database from
     * ZFE_Model_Mongo.
     */
    final public static function getDatabase()
    {
        return ZFE_Model_Mongo::getDatabase();
    }

}

