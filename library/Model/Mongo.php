<?php

/**
 * Because ACQ objects will have extra meta-data as subdocuments, we have to
 * separate the actual data model into a 'model' subdocument. Some ZFE_Model_Mongo
 * functions need to be aware of this and are therefore overloaded here. These
 * functions are get(), save() and map().
 */
class ACQ_Model_Mongo extends ZFE_Model_Mongo
{
    protected static $modelKey = 'model';

    /**
     * Overload the get() function
     *
     * Because in ACQ_Model_Mongo, the actual model data is in the 'model'
     * subdocument, we need to update the identifier field to 'model.id' in this
     * function to be able to select the documents.
     */
    public static function get($id, $field = null)
    {
        if ($field === null) {
            $field = static::$_identifierField;
        }
        if (!empty(static::$modelKey)) {
            $field = static::$modelKey . '.' . $field;
        }

        return parent::get($id, $field);
    }

    /**
     * Overload the getMaximum() function
     *
     * Because in ACQ_Model_Mongo, the actual model data is in the 'model'
     * subdocument, we need to update the requested field to 'model.' . $field
     * in this function to be able to select the field.
     */
    public static function getMaximum($field, $filter = array())
    {
        if (empty(static::$modelKey)) return parent::getMaximum($field, $filter);

        $field = static::$modelKey . '.' . $field;

        return parent::getMaximum($field, $filter);
    }

    /**
     * Overload the save() function
     *
     * Because the actual model is stored in the 'model' subdocument,
     * we need to tell Mongo to update the 'model' subdocument instead
     * of overwriting the whole document.
     *
     * TODO Would be best if this was moved into ZFE_Model_Mongo?
     */
    public function save()
    {
        $collection = self::getCollection();

        // Remove from model if value is null
        foreach($this->_data as $key => $val) {
            if (is_null($val)) unset($this->_data[$key]);
        }

        $data = empty(static::$modelKey) ? $this->_data : array(static::$modelKey => $this->_data);

        if (isset($this->_id)) {
            $collection->update(
                array('_id' => $this->_id),
                array('$set' => $data)
            );
        } else {
            $collection->save($data);

            $this->_id = $data['_id'];
            if (empty(static::$modelKey)) {
                unset($data['_id']);
                $this->_data = $data;
            } else {
                $this->_data = $data[static::$modelKey];
            }
        }

        // Delete the reference cache
        $objectId = (string) $this->_id;
        if (isset(static::$_refCache[$objectId])) {
            unset(static::$_refCache[$objectId]);
        }

        // Remove from cache after saving
        $class = get_called_class();
        unset(self::$_cache[$class][$this->getIdentifier()]);

        // Run on*Updated functions on changed fields and clear it
        foreach($this->_changedFields as $fld => $oldValue) {
            $fn = ZFE_Util_String::toCamelCase("on-" . $fld . "-updated");
            if (method_exists($this, $fn)) $this->$fn($oldValue);
        }
        $this->_changedFields = array();
    }

    /**
     * Inject a dt_delete: { $exists: false } into the default query
     *
     * This skips deleted articles for example. Should also work for other objects.
     */
    public static function __callStatic($name, $args)
    {
        $dt_delete = empty(static::$modelKey) ? 'dt_delete' : static::$modelKey . '.dt_delete';

        // Depending on the call, the filter is in a different argument
        if ($name == 'count') {
            $args[0][$dt_delete] = array('$exists' => false);
        }

        if ($name == 'distinct') {
            $args[0] = empty(static::$modelKey) ? $args[0] : static::$modelKey . '.' . $args[0];
            $args[1][$dt_delete] = array('$exists' => false);
        }

        return parent::__callStatic($name, $args);
    }

    /**
     * Inject a dt_delete: { $exists: false } into the default query
     *
     * This skips deleted articles for example. Should also work for other objects.
     *
     * Had to do this separately for the find method since it is defined in the 
     * ZFE_Model_Mongo class. A solution would be to have ZFE_Model_Mongo::__callStatic 
     * call _doXxx() if it exists, and then we wouldn't need to have this function here, 
     * since it would be dealt with by this class's __callStatic function.
     */
    public static function find($args = array())
    {
        if (!isset($args['query'])) $args['query'] = array();

        $dt_delete = empty(static::$modelKey) ? "dt_delete" : static::$modelKey . ".dt_delete";
        $args['query'][$dt_delete] = array('$exists' => false);

        if (isset($args['fields']) && !empty(static::$modelKey)) {
            $modelKey = static::$modelKey;
            array_walk($args['fields'], function(&$item) use ($modelKey) {
                $item = $modelKey . "." . $item;
            });
        }

        return parent::find($args);
    }

    /**
     * Overload the count function
     *
     * This is important so that find() and count() are consistent
     */
    public static function count($query = array())
    {
        $dt_delete = empty(static::$modelKey) ? "dt_delete" : static::$modelKey . ".dt_delete";
        $query[$dt_delete] = array('$exists' => false);

        return parent::count($query);
    }

    /**
     * Overload the map function
     *
     * This is because the model needs to be initialized from the
     * 'model' subdocument.
     */
    public static function map($data)
    {
        if (empty(static::$modelKey)) return parent::map($data);

        $_data = $data[static::$modelKey];
        // Warning: this loses anyhing besides the model. Would have been better not to query the other fields in the first place.

        if (isset($data['_id'])) {
            $_data = array_merge(
                array('_id' => $data['_id']),
                $_data
            );
        }

        return parent::map($_data);
    }

    /**
     * getRandom
     *
     * Randomly gets a number of elements from the collection.
     * Temporary function until MongoDB has an internal random function.
     *
     * This is of acceptable speed: about 0.025s to get the IDs, and about
     * 0.015s to get the article models
     */
    public static function getRandom($n, $filter = array())
    {
        // Running time: 20us
        $collection = static::getCollection();
        $result = $collection->find($filter, array('_id' => 1));

        // Running time: 0.025s
        $ids = array_map(function($r) { return $r['_id']; }, array_values(iterator_to_array($result)));

        $total = count($ids);
        if ($total == 0) return array();

        // Start with the whole set
        $picked = $ids;

        // If there are more in the whole set than asked for,
        // randomly select $n from the set
        if ($total > $n) {
            $picked = array();
            while(count($picked) < $n) {
                $i = mt_rand(0, $total - 1);
                $picked[$i] = $ids[$i];
            }
        }

        $idFilter = array('query' => array('_id' => array_values($picked)));

        // Running time: 0.015s
        $records = static::find($idFilter);
        $records = $records['result'];

        shuffle($records);
        return $records;
    }

    /**
     * Get a statistics field
     *
     * ACQ models store the actual data in document.model. Other metadata
     * is stored besides it. Statistics are one type of metadata, and
     * the stats values are stored in document.stats.*
     *
     * Stats are very volatile, and are not stored within the class at
     * runtime. Use statsInc whenever possible, for an atomic update
     * function.
     *
     * Only valid if !empty(static::$modelKey).
     *
     * Should maybe be metaGet('stats', $key)? Or even meta('get', 'stats', $key)?
     *
     * Ex:
     * $views = $article->statsGet('views');
     *
     * @param      string  $key    Stats field to get
     * @return     mixed   Value of the stats field
     */
    public function statsGet($key)
    {
        if (!isset($this->_id)) {
            throw new Exception("Can't have stats on non-existing objects");
        }

        $query = array('_id' => $this->_id);
        $fields = array("stats.$key" => true);
        $stats = static::getCollection()->findOne($query, $fields);

        if (!array_key_exists('stats', $stats) || !array_key_exists($key, $stats['stats'])) {
            return null;
        }

        return $stats['stats'][$key];
    }

    /**
     * Set a statistics field
     *
     * Ex:
     * $article->stats('views', $views + 1);
     *
     * @param      string  $key    Stats field to get / set
     * @param      mixed   $value  Value to change to
     */
    public function statsSet($key, $value = null)
    {
        if (!isset($this->_id)) {
            throw new Exception("Can't have stats on non-existing objects");
        }

        static::getCollection()->update(
            array('_id' => $this->_id),
            array('$set' => array("stats.$key" => $value))
        );

        return $value;
    }

    /**
     * Increment a statistics field
     *
     * Redundant with statsSet(statsGet()+$inc), but this function
     * is more efficient, and atomic.
     *
     * @param      string  $key     Field to increment
     * @param      int     $amount  Amount to increment by
     */
    public function statsInc($key, $amount)
    {
        if (!isset($this->_id)) {
            throw new Exception("Can't have stats on non-existing objects");
        }

        static::getCollection()->update(
            array('_id' => $this->_id),
            array('$inc' => array("stats.$key" => $amount))
        );
    }
}
