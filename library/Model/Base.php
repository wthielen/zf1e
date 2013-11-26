<?php

class ZFE_Model_Base
{
    protected $_data;

    public function __construct()
    {
        $this->_data = array();
    }

    /**
     * If there is a special setter function for the given
     * key in this class, run it through that setter function. 
     * Otherwise, just set the key-value pair in the data 
     * member.
     */
    public function __set($key, $val)
    {
        $setter = "_set" . ucfirst(strtolower($key));
        if (method_exists($this, $setter)) {
            $this->$setter($val);
        } else {
            $this->_data[$key] = $val;
        }
    }

    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function toArray()
    {
        return $this->_data;
    }

    /**
     * Resets the data member, and sets the fields in such a fashion
     * that it eventually triggers any setter functions, whenever defined
     */
    public function init($data)
    {
        $this->_data = array();

        foreach($data as $key => $val) $this->$key = $val;
    }
}
