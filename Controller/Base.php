<?php

class ZFE_Controller_Base extends Zend_Controller_Action
{
    /**
     * Constructor
     *
     * After calling the parent constructor, this constructor
     * will take care of the default resource files (CSS and
     * JavaScript) to be included, using the default view
     * helpers.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     * @return void
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()
    )
    {
        parent::__construct($request, $response, $invokeArgs);

        // Initialize view
        if (!isset($this->view)) {
            $this->initView();
        }

        // Get controller and action names
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();

        // Automatically add CSS and JS
        $headLink = $this->view->headLink();
        $headLink->appendStylesheet('/css/default.css');
        $headLink->appendStylesheet('/css/' . $controller . '.css');
        $headLink->appendStylesheet('/css/' . $controller . '/' . $action . '.css');

        $headScript = $this->view->headScript();
        $headScript->appendFile('/js/default.js');
        $headScript->appendFile('/js/' . $controller . '.js');
        $headScript->appendFile('/js/' . $controller . '/' . $action . '.js');
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
        $action = $this->getRequest()->getActionName();

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
}
