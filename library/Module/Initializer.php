<?php

/**
 * The initializer to run the active module's bootstrap
 *
 * Based on:
 * http://offshootinc.com/blog/2011/02/11/modul-bootstrapping-in-zend-framework/
 */
abstract class ZFE_Module_Initializer extends Zend_Application_Bootstrap_BootstrapAbstract
{
    /** @var ZFE_Module_Bootstrap */
    protected $_bootstrap;

    public function __construct($bootstrap)
    {
        if (!$bootstrap instanceof ZFE_Module_Bootstrap)
        {
            throw new Zend_Application_Bootstrap_Exception(
                __CLASS__ . '::__construct expects an instance of ZFE_Module_Bootstrap'
            );
        }

        $this->_bootstrap = $bootstrap;
    }

    public function run()
    {
    }

    public function getBootstrap()
    {
        return $this->_bootstrap;
    }

    final public function initialize($resource = null)
    {
        $this->_bootstrap($resource);

        return $this;
    }
}
