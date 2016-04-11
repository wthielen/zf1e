<?php

class MailTest extends ZFE_Test_PHPUnit_ControllerTestCase
{
    public function setUp()
    {

    }

    public function testInstatiation()
    {
        $mail = new ZFE_Mail(array(
            'layoutPath' => '/path/to/layouts',
            'scriptPath' => '/path/to/scripts',
            'language' => 'ja',
            'defaultLanguage' => 'en',
            'defaultLayout' => 'default',
        ));

        $this->assertTrue($mail instanceof ZFE_Mail);
    }
}
