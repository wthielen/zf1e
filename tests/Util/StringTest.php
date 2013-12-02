<?php

class ZFE_Util_StringTest extends PHPUnit_Framework_TestCase
{
    public function testTrimNull()
    {
        $this->assertEquals("", ZFE_Util_String::trim(null));
    }

    public function testTrimLeft()
    {
        $this->assertEquals("This is expected.", ZFE_Util_String::trim(" This is expected."));
        $this->assertEquals("This is expected.", ZFE_Util_String::trim("  This is expected."));
    }

    public function testTrimRight()
    {
        $this->assertEquals("This is expected.", ZFE_Util_String::trim("This is expected. "));
        $this->assertEquals("This is expected.", ZFE_Util_String::trim("This is expected.  "));
    }

    public function testTrimBoth()
    {
        $this->assertEquals("This is expected.", ZFE_Util_String::trim(" This is expected. "));
        $this->assertEquals("This is expected.", ZFE_Util_String::trim("  This is expected.  "));
    }

    public function testTrimWideSpaceLeft()
    {
        $expected = "予期される　値";

        $this->assertEquals($expected, ZFE_Util_String::trim("　予期される　値"));
        $this->assertEquals($expected, ZFE_Util_String::trim("　　予期される　値"));
    }

    public function testTrimWideSpaceRight()
    {
        $expected = "予期される　値";

        $this->assertEquals($expected, ZFE_Util_String::trim("予期される　値　"));
        $this->assertEquals($expected, ZFE_Util_String::trim("予期される　値　　"));
    }

    public function testTrimWideSpaceBoth()
    {
        $expected = "予期される　値";

        $this->assertEquals($expected, ZFE_Util_String::trim("　予期される　値　"));
        $this->assertEquals($expected, ZFE_Util_String::trim("　　予期される　値　　"));
    }
}
