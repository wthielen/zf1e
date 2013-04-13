<?php

class ZFE_Model_Db extends ZFE_Model_Base
{
    protected static $_pdo;

    public function __construct()
    {
        if (!isset(static::$_pdo)) self::_setupPdo();

    }

    protected static function _setupPdo()
    {
        if (!isset(static::$_dsn)) {
            $error = "No DSN specified in " . get_called_class();
            $error .= ": please specify a 'protected static \$_dsn = ...;'";
            throw new Exception($error);
        }

        $user = null;
        $password = null;

        // If it is MySQL, split user and password out
        $dsn = explode(":", static::$_dsn);
        if ('mysql' == $dsn[0]) {
            $parts = explode(";", $dsn[1]);
            foreach($parts as $i => $part) {
                $kv = explode('=', $part);
                $parts[$kv[0]] = $kv[1];
                unset($parts[$i]);
            }

            $user = @ZFE_Util_Core::value($parts['user']);
            $password = @ZFE_Util_Core::value($parts['password']);

            unset($parts['user']);
            unset($parts['password']);

            $_parts = array();
            foreach($parts as $k => $v) $_parts[] = "$k=$v";
            $dsn[1] = implode(";", $_parts);
        }
        $dsn = implode(":", $dsn);

        static::$_pdo = new PDO($dsn, $user, $password);
    }
}
