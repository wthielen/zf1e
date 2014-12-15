<?php

class ZFE_Controller_Base extends Zend_Controller_Action
{
    /**
     * Initializer
     *
     * This function will be called by the constructor. It
     * will take care of the default resource files (CSS and
     * JavaScript) to be included, using the default view
     * helpers.
     *
     * @return void
     */
    public function init()
    {
        // Initialize view
        $view = $this->initView();

        // Add the library's view helper path
        $libraryPath = realpath(dirname(__FILE__) . '/..');
        $view->addHelperPath($libraryPath . '/View/Helper', 'ZFE_View_Helper');
    }

    /**
     * Pre-dispatch routine
     *
     * This will replace the default pre-dispatch routine and call
     * the action-level pre-dispatch function, if it exists. This
     * gives us more flexibility in controlling the process flow.
     *
     * @return void
     */
    public function preDispatch()
    {
        // Get controller and action names
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();

        // Automatically add CSS and JS
        $cssFiles = array('default.css', $controller . '.css', $controller . '/' . $action . '.css');
        $headLink = $this->view->headLink();
        foreach($cssFiles as $file) {
            if (!Zend_Loader::isReadable(ZFE_Environment::getFilePath('/css/' . $file))) continue;
            $headLink->appendStylesheet('/css/' . $file);
        }

        $jsFiles = array('default.js', $controller . '.js', $controller . '/' . $action . '.js');
        $headScript = $this->view->headScript();
        foreach($jsFiles as $file) {
            if (!Zend_Loader::isReadable(ZFE_Environment::getFilePath('/js/' . $file))) continue;
            $headScript->appendFile('/js/' . $file);
        }

        // Call the action's pre-function
        $func = 'pre' . ucfirst(strtolower($action)) . 'Action';
        if (method_exists($this, $func)) $this->$func();
    }

    /**
     * Post-dispatch routine
     *
     * This will replace the default post-dispatch routine and call
     * the action-level post-dispatch function, if it exists. This
     * gives us more flexibility in controlling the process flow.
     *
     * @return void
     */
    public function postDispatch()
    {
        $action = $this->getRequest()->getActionName();

        $func = 'post' . ucfirst(strtolower($action)) . 'Action';
        if (method_exists($this, $func)) $this->$func();
    }

    /**
     * expectParams
     *
     * A variant of getParams, but checks whether the given expected parameters
     * have been set. If any of the expected parameters are not set, then it
     * will redirect to the given URL, or throw a request exception.
     */
    public function expectParams($expected, $redirect = null)
    {
        if (is_scalar($expected)) $expected = array($expected);

        $request = $this->getRequest();
        $params = $request->getParams();
        $expected = array_combine($expected, $expected);

        $missing = array_diff_key($expected, $params);

        if (!empty($missing)) {
            if (is_null($redirect)) throw new Zend_Controller_Request_Exception("The following expected parameters were missing from the request: " . implode(", ", $missing));

            $this->_redirect($redirect);
        }

        return $params;
    }
}
