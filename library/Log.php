<?php

/**
 * Class ZFE_Log
 * `@method static crit($message)
 * @method static warn($message)
 * @method static notice($message)
 */
abstract class ZFE_Log
{
    /**
     * @var Zend_Log
     */
    private static $logger;

    /**
     * @return Zend_Log
     */
    protected static function getLogger()
    {
        if (is_null(self::$logger)) {
            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');

            self::$logger = $bootstrap->getResource('Log');
        }

        return self::$logger;
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    public static function __callStatic($method, $arguments)
    {
        if (count($arguments) == 0) {
            return;
        }

        $logger = self::getLogger();
        $message = $arguments[0];

        if (is_null($logger)) {
            error_log($message);
            return;
        }

        try {
            call_user_func_array(array($logger, $method), $arguments);
        } catch (Zend_Log_Exception $e) {
            error_log($message);
        }
    }
}
