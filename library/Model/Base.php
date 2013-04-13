<?php

class ZFE_Model_Base
{
    protected $_data;

    public function __construct()
    {
        $this->_data = array();
    }

    public function __set($key, $var)
    {
        $this->_data[$key] = $var;
    }

    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }
}
