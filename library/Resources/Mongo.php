<?php

/**
 * An application plugin resource for connecting to a MongoDB server
 */
class ZFE_Resource_Mongo extends Zend_Application_Resource_ResourceAbstract
{
    private $connection;

    private $host;
    private $port;
    private $username;
    private $password;
    private $database;

    /**
     * Initialize the plugin
     *
     * When no application options have been specified in the config.ini, then
     * it will use the default values from php.ini
     */
    public function init()
    {
        $o = $this->getOptions();

        if (empty($o['database']))
        {
            throw new Zend_Application_Resource_Exception('Please specify at least the Mongo database to use: resources.mongo.database');
        }

        $this->host = isset($o['host']) ? $o['host'] : ini_get('mongo.default_host');
        $this->port = isset($o['port']) ? $o['port'] : ini_get('mongo.default_port');
        $this->username = isset($o['username']) ? $o['username'] : null;
        $this->password = isset($o['password']) ? $o['password'] : null;
        $this->database = $o['database'];

        return $this;
    }

    /**
     * Creates the connection URI and returns the MongoClient instance.
     *
     * The PECL Mongo PHP library already does persistent connections since 
     * version 1.2, but here it is just for saving the process of composing 
     * the URI every time.
     */
    public function getConnection()
    {
        if (is_null($this->connection)) {
            $uri = "mongodb://";
            if ($this->username && $this->password) {
                $uri .= $this->username . ":" . $this->password . "@";
            }
            $uri .= $this->host . ":" . $this->port . "/" . $this->database;
            $this->connection = new MongoClient($uri);
        }

        return $this->connection;
    }
}
