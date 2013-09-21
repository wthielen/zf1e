<?php

/**
 * A helper class to add the jQuery JS framework
 */
class ZFE_Controller_Helper_Jquery extends Zend_Controller_Action_Helper_Abstract
{
    private static $options;

    // Sets default options
    private static function _setDefault()
    {
        self::$options = array(
            // jQuery options
            'enableUi' => true,

            // CDN options
            'useCdn' => false,
            'cdnHost' => '//code.jquery.com',
            'coreVersion' => 'stable',
            'uiVersion' => null,
            'uiTheme' => 'smoothness',

            // Local options
            'jsPath' => '/js/jquery',
            'cssPath' => '/css/jquery'
        );

        // Fetch options from the application's configuration
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $resource = $bootstrap->getPluginResource('Jquery');

        if ($resource) {
            self::$options = array_merge(self::$options, $resource->getOptions());
        }
    }

    public function direct()
    {
        // Set default options
        if (is_null(self::$options)) self::_setDefault();

        self::$options['useCdn'] ? $this->cdn() : $this->local();
    }

    // Use CDN to include jQuery files
    private function cdn()
    {
        // Compose the base name, include requested version if required
        $basename = 'jquery';
        if (self::$options['coreVersion'] !== 'stable') {
            $basename = 'jquery-' . self::$options['coreVersion'];
        }

        // Use minified version if we are on a production environment
        if (APPLICATION_ENV == 'production') $basename .= '.min';

        // Compose CDN URL for this JS asset
        $url = self::$options['cdnHost'] . '/' . $basename . '.js';

        // Add file to view
        $view = $this->getActionController()->view;
        $view->headScript()->appendFile($url);

        // If jQuery-UI is also requested
        if (self::$options['enableUi']) {
            // The jQuery-UI version needs to be set when using a CDN
            if (is_null(self::$options['uiVersion'])) {
                throw new Exception('Please set a uiVersion in the jquery resource if you want to use jQuery UI: resources.jquery.uiVersion, or disable jQuery UI: resources.jquery.enableUi = 0');
            }

            // Set basename, path and compose URL based on the application environment
            $basename = 'jquery-ui';
            $path = '/ui/' . self::$options['uiVersion'];

            $url = APPLICATION_ENV == 'production'
                ? self::$options['cdnHost'] . $path . '/' . $basename . '.min.js'
                : self::$options['cdnHost'] . $path . '/' . $basename . '.js';

            // Add file to view
            $view->headScript()->appendFile($url);

            // Set path and compose URL for the theme CSS
            $path .= '/themes/' . self::$options['uiTheme'];
            $url = self::$options['cdnHost'] . $path . '/' . $basename . '.css';
            $view->headLink()->appendStylesheet($url);
        }
    }

    // Use locally stored files
    // The basename of the files need to be "jquery" and "jquery-ui".
    // We recommend creating symlinks to the versioned files.
    private function local()
    {
        // Set basename and filename
        $basename = 'jquery';
        $js = $basename . '.js';

        // Checks if a minified version exists when we are on a production environment
        if (APPLICATION_ENV == 'production') {
            $jsPath = $_SERVER['DOCUMENT_ROOT'] . self::$options['jsPath'];
            if (Zend_Loader::isReadable($jsPath . '/' . $basename . '.min.js')) {
                $js = $basename . '.min.js';
            }
        }

        // Add file to view
        $view = $this->getActionController()->view;
        $view->headScript()->appendFile(self::$options['jsPath'] . '/' . $js);

        // If jQuery-UI is also requested
        if (self::$options['enableUi']) {
            // Set basename and file name
            $basename = 'jquery-ui';
            $js = $basename . '.js';

            // Check if a minified version exists when we are on a production environment
            if (APPLICATION_ENV == 'production') {
                $jsPath = $_SERVER['DOCUMENT_ROOT'] . self::$options['jsPath'];
                if (Zend_Loader::isReadable($jsPath . '/' . $basename . '.min.js')) {
                    $js = $basename . '.min.js';
                }
            }

            // Add the JS asset to view
            $view->headScript()->appendFile(self::$options['jsPath'] . '/' . $js);

            // Add the CSS asset to view
            $css = $basename . '.css';
            $view->headLink()->appendStylesheet(self::$options['cssPath'] . '/' . $css);
        }
    }
}
