<?php

/**
 * A helper class to add the Bootstrap CSS framework
 */
class ZFE_Controller_Helper_Bootstrap extends Zend_Controller_Action_Helper_Abstract
{
    private $_defaultOptions = array(
        'minified' => true,
        'css' => '/css',
        'js' => '/js'
    );

    private $_options;

    public function direct($options = array())
    {
        // Set default options as to where the CSS and JS directories are
        if (is_null($this->_options)) {
            $this->_options = $this->_defaultOptions;
        }

        // Merge passed options with default options
        $this->_options = array_merge($this->_defaultOptions, $options);

        // Store basename here
        $basename = 'bootstrap';

        $css = $basename . '.css';
        $js = $basename . '.js';

        // Check if minified versions exist for production environment
        if ($this->_options['minified']) {
            $cssPath = $_SERVER['DOCUMENT_ROOT'] . $this->_options['css'];
            if (Zend_Loader::isReadable($cssPath . '/' . $basename . '.min.css')) {
                $css = $basename . '.min.css';
            }

            $jsPath = $_SERVER['DOCUMENT_ROOT'] . $this->_options['js'];
            if (Zend_Loader::isReadable($jsPath . '/' . $basename . '.min.js')) {
                $js = $basename . '.min.js';
            }
        }

        // Add files to view
        $controller = $this->getActionController();
        $controller->view->headLink()->appendStylesheet($this->_options['css'] . '/' . $css);
        $controller->view->headScript()->appendFile($this->_options['js'] . '/' . $js);
    }
}
