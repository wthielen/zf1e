<?php

/**
 * A helper class to add the Bootstrap CSS framework
 */
class ZFE_Controller_Helper_Bootstrap extends Zend_Controller_Action_Helper_Abstract
{
    private $_options;

    public function direct($responsive = true, $options = array())
    {
        // Set default options as to where the CSS and JS directories are
        if (is_null($this->_options)) {
            $this->_options = array(
                'css' => '/css', 
                'js' => '/js'
            );
        }

        // Merge passed options with default options
        $this->_options = array_merge($this->_options, $options);

        // Determine basename of the file based on responsive flag
        $basename = $responsive ? 'bootstrap-responsive' : 'bootstrap';

        $css = $basename . '.css';
        $js = $basename . '.js';

        // Check if minified versions exist for production environment
        if (APPLICATION_ENV == 'production') {
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
