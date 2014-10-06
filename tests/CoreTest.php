<?php

class ZFE_CoreTest extends PHPUnit_Framework_TestCase
{
    public function testValueNull()
    {
        $this->assertNull(ZFE_Core::value(null));
    }

    public function testValueDefault()
    {
        $values = array(
            "default",
            0,
            array()
        );

        foreach($values as $val) {
            $this->assertEquals($val, ZFE_Core::value(null, $val));
        }
    }

    private function doAssert($val)
    {
        $this->assertEquals($val, ZFE_Core::value($val));
    }

    public function testValueInteger()
    {
        $this->doAssert(1);
    }

    public function testValueFloat()
    {
        $this->doAssert(1.0);
    }

    public function testValueString()
    {
        $this->doAssert("string");
    }

    public function testValueArray()
    {
        $obj = new stdclass();
        $obj->int = 1;
        $obj->txt = "string";
        $this->doAssert(array(1, "string", $obj));
    }

    public function testValueObject()
    {
        $obj = new stdclass();
        $obj->int = 1;
        $obj->txt = "string";
        $obj->arr = array(2, "text");
        $this->doAssert($obj);
    }
}
