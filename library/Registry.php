<?php

/**
 * Extension of Zend_Registry but can store and handle closures for lazy loading
 * Also, used to instantiate new model objects
 *
 * @category   ZFE
 * @package    ZFE_Registry
 */

class ZFE_Registry extends Zend_Registry
{
    /**
     * getter method, basically same as offsetGet().
     *
     * This method can be called from an object of type Zend_Registry, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index - get the value associated with $index
     * @return mixed
     * @throws Zend_Exception if no entry is registered for $index.
     */
    public static function get($index)
    {
        $instance = self::getInstance();

        if (! $instance->offsetExists($index)) {
            throw new Zend_Exception("No entry is registered for key '$index'");
        }

        // get the value for this $index
        // if a closure, store the result for this $index
        $return = $instance->offsetGet($index);
        if ($return instanceof \Closure) {
            self::set($index, $return());
        }

        return $instance->offsetGet($index);
    }

}
