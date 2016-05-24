<?php
/**
 * Class ZFE_Model_RefCache
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package ZFE
 */
class ZFE_Model_RefCache
{
    /**
     * @var array
     */
    protected static $data = array();

    /**
     * @param string $mongoObjectId
     * @param string $key
     * @param mixed $value
     */
    public static function set($mongoObjectId, $key, $value)
    {
        if (!isset(static::$data[$mongoObjectId])) {
            static::$data[$mongoObjectId] = array();
        }
        static::$data[$mongoObjectId][$key] = $value;
    }

    /**
     * @param string $mongoObjectId
     * @param string $key
     * @return null|mixed
     */
    public static function get($mongoObjectId, $key)
    {
        if (!isset(static::$data[$mongoObjectId]) || !isset(static::$data[$mongoObjectId][$key])) {
            return null;
        }

        return static::$data[$mongoObjectId][$key];
    }

    /**
     * @param string $mongoObjectId
     */
    public static function delete($mongoObjectId)
    {
        if (isset(static::$data[$mongoObjectId])) {
            unset(static::$data[$mongoObjectId]);
        }
    }
}
