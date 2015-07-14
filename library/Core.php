<?php

abstract class ZFE_Core
{
    static $browserinfo;

    public static function value($val, $default = null)
    {
        return @isset($val) ? $val : $default;
    }

    /**
     * Type-casts an object from one type to another
     */
    public static function cast($obj, $cls)
    {
        if (!class_exists($cls) || get_class($obj) == $cls) {
            return $obj;
        }

        return unserialize(
            preg_replace('/^O:\d+:"[^"]*"/', 'O:' . strlen($cls) . ':"' . $cls . '"', serialize($obj))
        );
    }

    /**
     * Gets the parent classes of an instance
     */
    public static function getParents($obj, $parents = array())
    {
        if ($parent = get_parent_class($obj)) {
            $parents[] = $parent;
            return self::getParents($parent, $parents);
        }

        return $parents;
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
            if (!isset($_SESSION['browserinfo'])) {
                $info = @get_browser();
                $_SESSION['browserinfo'] = $info;
            }

            self::$browserinfo = $_SESSION['browserinfo'];
        }

        return self::$browserinfo;
    }

    /**
     * Checks if the given IP is a local IP address.
     * If no IP is given, it checks the REMOTE_ADDR IP
     */
    public static function isLocal($ipaddr = null)
    {
        if (is_null($ipaddr)) {
            $front = Zend_Controller_Front::getInstance();
            $request = $front->getRequest();
            $ipaddr = $request->getClientIp();
        }

        $octets = array_map("intval", explode(".", $ipaddr));
        if ($octets[0] == 10 || $octets[0] == 127) return true;
        if ($octets[0] == 192 && $octets[1] == 168) return true;
        if ($octets[0] == 172 && $octets[1] >= 16 && $octets[1] < 32) return true;

        return false;
    }

    /**
     * Checks if the given URL is on the same domain as us.
     */
    public static function sameDomain($url)
    {
        $domain = $_SERVER['HTTP_HOST'];
        $urlDomain = parse_url($url, PHP_URL_HOST);

        return $domain == $urlDomain;
    }

    public static function getFullUrl($path)
    {
        $domain = $_SERVER['HTTP_HOST'];
        $https = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']);
        $proto = $https ? 'https://' : 'http://';

        return $proto . $domain . $path;
    }

    /**
     * A convenience function to wrap cache loading/saving. The $fn function
     * needs to be a lambda function that does not accept any arguments. If you
     * need to pass scope variables into the function, use:
     * function() use ($var1, $var2) { ... }
     *
     * Example call:
     * ZFE_Core::cache("result_of_heavy_calc", function() use ($x, $y) {
     *     // Do something heavy with $x and $y
     *     $ret = ... $x ... $y ...;
     *
     *     return $ret;
     * }, 3600);
     *
     * To bypass the cache, set the fourth parameter to be true.
     */
    public static function cache($cache_id, $fn, $expire = null, $bypass = false)
    {
        $cache = Zend_Registry::get('Zend_Cache');
        if (is_null($cache)) return $fn();

        if (($ret = $cache->load($cache_id)) === false || $bypass) {
            $ret = $fn();
            $cache->save($ret, $cache_id, array(), $expire);
        }

        return $ret;
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

    public static function halt()
    {
        $vars = func_get_args();

        self::dump(count($vars) == 1 ? array_shift($vars) : $vars);

        if (Zend_Registry::isRegistered('ZFE_DebugFilter')) {
            $debugFilter = Zend_Registry::get('ZFE_DebugFilter');
            if ($debugFilter->isAllowed()) die("Halted");
        }

        if (ZFE_Environment::isDevelopment()) die("Halted");
    }

    /**
     * The private function _dump() that does the actual dump for dump().
     * It adds the filename and linenumber from the backtrace in pretty-print
     * format, so that you can quickly see where you left your dump() calls.
     *
     * It takes into account whether xdebug is loaded or not. Xdebug already puts
     * the output in a <pre /> block, so we can skip that when it is loaded.
     *
     * Added CLI-specific routines
     */
    private static function _dump()
    {
        $vars = func_get_args();

        // Get backtrace and find the first entry where it calls the
        // ZFE_Core functions
        $bt = debug_backtrace();
        do {
            $source = next($bt);
        } while($source['file'] === __FILE__);

        $cli = php_sapi_name() == 'cli';
        $file = str_replace(realpath(APPLICATION_PATH . "/.."), "", $source['file']);
        if ($cli) {
            echo "\033[01;31m(root)\033[0m" . $file . ":" . $source['line'] . " dumped:" . PHP_EOL;
        } else {
            echo "<p>(root)<b>" . $file . "</b>:" . $source['line'] . " dumped:</p>" . PHP_EOL;
        }

        if (!$cli && (!extension_loaded("xdebug") || ini_get('html_errors') == 0)) echo "<pre>";
        call_user_func_array('var_dump', $vars);
        if (!$cli && (!extension_loaded("xdebug") || ini_get('html_errors') == 0)) echo "</pre>";

        if ($cli) echo PHP_EOL;
    }
}
