<?php
/**
 * An Enum abstract class to create your own Enums
 *
 * Example with translated string values:
 * abstract class XYZ_Enum_Color extends ZFE_Enum_Abstract
 * {
 *     const WHITE = 1; // Can be a string too like 'white'
 *     const BLACK = 2;
 *     const RED = 3;
 *     const BLUE = 4;
 *
 *     public static str($item)
 *     {
 *         $ml = Zend_Registry::get('ZFE_MultiLanguage');
 *         $ret = parent::str($item);
 *
 *         if ($item == self::WHITE) $ret = $ml->_('COLOR_WHITE');
 *         if ($item == self::BLACK) $ret = $ml->_('COLOR_BLACK');
 *         // etc...
 *
 *         return $ret;
 *     }
 * }
 *
 * Then you can have: $color = XYZ_Enum_Color::BLACK;
 * and: echo XYZ_Enum_Color::str($color);
 *
 * And to get a list of values, for your select pulldown for example,
 * call XYZ_Enum_Color::getValues()
 */
abstract class ZFE_Enum_Abstract
{
    public static function exists($val)
    {
        return in_array($val, static::getKeys());
    }

    public static function getKeys()
    {
        $reflection = new ReflectionClass(get_called_class());

        return $reflection->getConstants();
    }

    public static function getValues()
    {
        $reflection = new ReflectionClass(get_called_class());

        $constants = $reflection->getConstants();

        $ret = array();
        foreach($constants as $val) $ret[$val] = static::str($val);
        return $ret;
    }

    public static function str($item) {
        return '(unknown)';
    }
}
