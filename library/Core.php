<?php

abstract class ZFE_Core
{
    static $browserinfo;

    public static function value($val, $default = null)
    {
        return @isset($val) ? $val : $default;
    }

    /**
     * Swaps the value of two variables
     */
    public static function swap(&$x, &$y)
    {
        $tmp = $y;
        $y = $x;
        $x = $tmp;
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

    /**
     * Gets the browser information and caches it in a static variable
     */
    public static function getBrowser() {
        if (is_null(self::$browserinfo)) {
            self::$browserinfo = @get_browser();
        }

        return self::$browserinfo;
    }

    /**
     * Dump the given variables. If the DebugFilter resource is enabled,
     * it will check the client's IP address and eventually the user agent
     * against the allowed values, before dumping the variables. If not
     * enabled, it will simply check whether the application environment
     * is "development" or a variant thereof.
     */
    public static function dump()
    {
        $vars = func_get_args();

        if (Zend_Registry::isRegistered('ZFE_DebugFilter')) {
            $debugFilter = Zend_Registry::get('ZFE_DebugFilter');
            if ($debugFilter->isAllowed()) {
                call_user_func_array(array(get_called_class(), '_dump'), $vars);
            }

            return;
        }

        if (ZFE_Environment::isDevelopment()) {
            call_user_func_array(array(get_called_class(), '_dump'), $vars);
        }
    }

    /**
     * The private function _dump() that does the actual dump for dump().
     * It adds the filename and linenumber from the backtrace in pretty-print
     * format, so that you can quickly see where you left your dump() calls.
     *
     * It takes into account whether xdebug is loaded or not. Xdebug already puts
     * the output in a <pre /> block, so we can skip that when it is loaded.
     */
    private static function _dump()
    {
        $vars = func_get_args();

        $bt = debug_backtrace();
        $source = $bt[2];

        $file = str_replace(realpath(APPLICATION_PATH . "/.."), "", $source['file']);
        echo "<p>(root)<b>" . $file . "</b>:" . $source['line'] . " dumped:</p>" . PHP_EOL;

        if (!extension_loaded("xdebug")) echo "<pre>";
        call_user_func_array('var_dump', $vars);
        if (!extension_loaded("xdebug")) echo "</pre>";
    }
}
