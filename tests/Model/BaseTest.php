<?php

class ZFE_Model_BaseTest extends PHPUnit_Framework_TestCase
{
    public $testArray = array(
        'field1' => 'value1',
        'field2' => array(0, 1, 2, 3),
        'field3' => true,
        'field4' => false,
        'field5' => 5,
        'field6' => 8.0
    );

    public function testAssignment()
    {
        // Test using assignment
        $obj = new ZFE_Model_Base();
        foreach($this->testArray as $fld => $val) {
            $obj->$fld = $val;
        }

        // Test the values
        foreach($this->testArray as $fld => $val) {
            $this->assertEquals($obj->$fld, $val);
        }
    }

    public function testInit()
    {
        // Test using init()
        $obj = new ZFE_Model_Base();
        $obj->init($this->testArray);

        // Test the values
        foreach($this->testArray as $fld => $val) {
            $this->assertEquals($obj->$fld, $val);
        }
    }

    public function testToArray()
    {
        $obj = new ZFE_Model_Base();
        $obj->init($this->testArray);

        $this->assertEquals($obj->toArray(), $this->testArray);
    }
}
