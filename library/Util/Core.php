<?php

abstract class ZFE_Util_Core
{
    public static function value($val, $default = null)
    {
        return @isset($val) ? $val : $default;
    }

    /**
     * Convenience function to get the language based on the
     * MultiLanguage resource, or in its absence based on the
     * locale
     */
    public static function getLanguage()
    {
        if (Zend_Registry::isRegistered('ZFE_MultiLanguage')) {
            $ml = Zend_Registry::get('ZFE_MultiLanguage');
            return $ml->getLanguage();
        }

        $locale = new Zend_Locale();
        return $locale->getLanguage();
    }
}
