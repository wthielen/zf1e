<?php

// http://framework.zend.com/manual/1.12/en/zend.test.phpunit.html

class ZFE_Test_PHPUnit_ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    /**
    * The zend test case class supresses the exceptions which
    * is a bit of a pain when getting setup. I've left this method
    * here so that exceptions are not suppressed
    */
    public function dispatch($url = null)
    {
        // redirector should not exit
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->setExit(false);

        // json helper should not exit
        $json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
        $json->suppressExit = true;

        $request    = $this->getRequest();
        if (null !== $url) {
            $request->setRequestUri($url);
        }
        $request->setPathInfo(null);

        $controller = $this->getFrontController();
        $this->frontController
             ->setRequest($request)
             ->setResponse($this->getResponse())
             ->throwExceptions(true) // replace false with true
             ->returnResponse(false);

        if ($this->bootstrap instanceof Zend_Application) {
            $this->bootstrap->run();
        } else {
            $this->frontController->dispatch();
        }
    }
}
