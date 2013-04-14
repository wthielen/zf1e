<?php

class ZFE_Model_Db extends ZFE_Model_Base
{
    protected static $_pdo;

    protected static $_tables;
    protected static $_tableFields = array();

    protected static $_readonly = array();
    protected static $_blacklist = array();

    protected static $_primaryFields = array();

    public function __construct()
    {
        // Only set up the PDO once per PHP run
        if (!isset(static::$_pdo)) self::_setupPdo();

        // Only fill in the tableFields once per PHP run
        if (0 == count(static::$_tableFields)) {
            static::_checkDatabase();

            foreach(static::$_tables as $table) {
                static::$_tableFields[$table] = array();

                $sql = "DESC $table";
                $desc = self::$_pdo->query($sql);
                foreach($desc as $row) {
                    static::$_tableFields[$table][] = $row['Field'];
                }
            }
        }
    }

    public function __set($key, $var)
    {
        // Add the ID field to the read-only fields list 
        $readonly = array_merge(static::$_readonly, array('id'));

        // Check if we are setting a read-only field
        if (in_array($key, $readonly)) {
            throw new Exception("Can not set read-only field '$key' in " . get_class($this));
        }

        $this->_data[$key] = $var;
    }

    public function __get($key)
    {
        // Check if we are accessing a black-listed field
        if (in_array($key, static::$_blacklist)) {
            throw new Exception("Can not access field '$key' from " . get_class($this));
        }

        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function save()
    {
    }

    protected static function _setupPdo()
    {
        // Check the DSN string
        if (!isset(static::$_dsn)) {
            $error = "No DSN specified in " . get_called_class();
            $error .= ": please specify a 'protected static \$_dsn = ...;'";
            throw new Exception($error);
        }

        $user = null;
        $password = null;
        $options = array();

        // If it is MySQL, extract user and password from the DSN
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

            // Update default fetch mode (maybe put this in the global
            // function scope)
            $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        }
        $dsn = implode(":", $dsn);

        static::$_pdo = new PDO($dsn, $user, $password, $options);
    }

    /**
     * Check database function
     *
     * Protected static function to check the database. This should be
     * defined by the derived classes themselves to create tables when
     * they do not exist.
     */
    protected static function _checkDatabase()
    {
        $error = "Missing a 'protected static function _checkDatabase()' in ";
        $error .= get_called_class();

        throw new Exception($error);
    }
}
