<?php

class ZFE_Util_StopwatchTest extends PHPUnit_Framework_TestCase
{
    public function testNull()
    {
        $this->assertNull(ZFE_Util_Stopwatch::get('non-existing'));
    }

    public function testTimings()
    {
        ZFE_Util_Stopwatch::start('timing');
        usleep(200000);
        ZFE_Util_Stopwatch::stop('timing');

        $timing = ZFE_Util_Stopwatch::get('timing');
        $this->assertEquals($timing['duration'], 0.2, '', 0.001);

        ZFE_Util_Stopwatch::start('timing');
        usleep(100000);
        ZFE_Util_Stopwatch::stop('timing');

        $timing = ZFE_Util_Stopwatch::get('timing');
        $this->assertEquals($timing['duration'], 0.1, '', 0.001);
    }

    public function testTrigger()
    {
        ZFE_Util_Stopwatch::trigger('trigger');
        $timing = ZFE_Util_Stopwatch::get('trigger');
        $this->assertArrayHasKey('start', $timing);
        $this->assertCount(1, $timing);

        ZFE_Util_Stopwatch::trigger('trigger');
        $timing = ZFE_Util_Stopwatch::get('trigger');
        $this->assertArrayHasKey('start', $timing);
        $this->assertArrayHasKey('stop', $timing);
        $this->assertArrayHasKey('duration', $timing);
        $this->assertCount(3, $timing);

        ZFE_Util_Stopwatch::trigger('trigger');
        $this->assertNull(ZFE_Util_Stopwatch::get('trigger'));
    }
}
