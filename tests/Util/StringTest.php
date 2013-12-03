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

    public function testChopNull()
    {
        $this->assertEquals("", ZFE_Util_String::chop(null, 50));
    }

    public function testChopShort()
    {
        $str = "This is a short string.";
        $this->assertEquals($str, ZFE_Util_String::chop($str, strlen($str)));
    }

    public function testChopLong()
    {
        $str = "This is a rather long string that should be chopped after twenty characters.";
        $expected = "This is a rather...";
        $this->assertEquals($expected, ZFE_Util_String::chop($str, 20));
    }

    public function testChopEllipsis()
    {
        $str = "This is a rather long string that should be chopped after forty characters, with a different ellipsis.";
        $expected = "This is a rather long string that s. . .";
        $this->assertEquals($expected, ZFE_Util_String::chop($str, 40, '. . .'));
    }

    public function testChopPunctuation()
    {
        $str = "This is a string. It has punctuation. No ellipsis should be added after punctuation.";
        $expected = "This is a string. It has punctuation.";
        $this->assertEquals($expected, ZFE_Util_String::chop($str, 37));
    }

    public function testChopWordsNull()
    {
        $this->assertEquals("", ZFE_Util_String::chopWords(null, 50));
    }

    public function testChopWordsShort()
    {
        $str = "This is a short string.";
        $this->assertEquals($str, ZFE_Util_String::chopWords($str, strlen($str)));
    }

    public function testChopWordsLong()
    {
        $str = "This is a rather long string that should be chopped after twenty characters.";
        $expected = "This is a rather...";
        $this->assertEquals($expected, ZFE_Util_String::chopWords($str, 20));
    }

    public function testChopWordsEllipsis()
    {
        $str = "This is a rather long string that should be chopped after forty characters, with a different ellipsis.";
        $expected = "This is a rather long string that. . .";
        $this->assertEquals($expected, ZFE_Util_String::chopWords($str, 40, '. . .'));
    }

    public function testChopWordsPunctuation()
    {
        $str = "This is a string. It has punctuation. No ellipsis should be added after punctuation.";
        $expected = "This is a string. It has punctuation.";
        $this->assertEquals($expected, ZFE_Util_String::chopWords($str, 37));
    }
}
