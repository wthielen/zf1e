<?php

// TODO load this with composer autoloader
require_once LIBRARY_PATH . '/Registry.php';

class RegistryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testGetSetMethods()
    {
        $id = 'example';
        $value = '123';

        ZFE_Registry::set($id, $value);

        $service = ZFE_Registry::get($id);

        $this->assertEquals($value, $service);
    }

    public function testGetSetWithClosureMethods()
    {
        $id = 'closure';
        $value = '123';

        ZFE_Registry::set($id, function() use ($value) {
            return $value;
        });

        $service = ZFE_Registry::get($id);

        $this->assertEquals($value, $service);
    }

    /**
     * @expectedException Zend_Exception
     */
    public function testGetWhenItemNotFoundMethods()
    {
        $id = 'idontexist';

        $service = ZFE_Registry::get($id);

        $this->assertEquals(null, $service);
    }
}
