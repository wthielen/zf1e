<?php

/**
 * An extended view helper for adding JS files. If the JS minifier resource is set up
 * it will combine the files, minify them, and store them in the cache directory as
 * configured by the minifier resource.
 */
class ZFE_View_Helper_HeadScript extends Zend_View_Helper_HeadScript
{
    protected $_minifier = false;

    /**
     * The constructor checks if the minifier resource has been set up, and gets the
     * CSS minifier from the resource.
     */
    public function __construct()
    {
        parent::__construct();

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        if ($bootstrap) {
            $resource = $bootstrap->getPluginResource('minifier');
            if ($resource) $this->_minifier = $resource->js;
        }
    }

    public function toString($indent = null)
    {
        // Return early if no minifier has been set up
        if (false === $this->_minifier) {
            return parent::toString($indent);
        }

        ZFE_Util_Stopwatch::trigger(__METHOD__);
        $container = $this->getContainer();
        $container->ksort();

        $items = $container->getArrayCopy();

        // Collect JS files
        $compressable = array();
        foreach($items as $key => $item) {
            if (isset($item->attributes['src'])) {
                $src = $item->attributes['src'];

                // If the source refers to a local file, process it
                if (preg_match('~^(\w+:)?//~', $src) === 0) {
                    $compressable[] = $item;
                    unset($items[$key]);
                }
            }
        }
        $container->exchangeArray($items);

        // Collect current data of the JS files
        $hash = '';
        $mtimes = array();
        foreach($compressable as $item) {
            $file = $_SERVER['DOCUMENT_ROOT'] . $item->attributes['src'];

            if (Zend_Loader::isReadable($file)) {
                $hash .= $item->attributes['src'];
                $mtimes[] = filemtime($file);
            }
        }

        // Check if the original JS files have been updated since the
        // last minification
        $regenerate = true;
        $filename = sha1($hash) . ".js";
        $cachedir = $this->_minifier->getCacheDir();
        $path = $_SERVER['DOCUMENT_ROOT'] . $cachedir;
        if (file_exists($path . "/" . $filename)) {
            $mtime = filemtime($path . "/" . $filename);
            $regenerate = array_reduce($mtimes, function($u, $v) use ($mtime) {
                return $u || $v > $mtime;
            }, false);
        }

        // If any JS file has been updated since the last minification
        // collect the content again, and store it in the cached version
        if ($regenerate) {
            $jsContent = '';
            foreach($compressable as $item) {
                $file = $_SERVER['DOCUMENT_ROOT'] . $item->attributes['src'];
                if (Zend_Loader::isReadable($file)) {
                    $jsContent .= file_get_contents($file);
                }
            }

            $jsContent = $this->_minifier->minify($jsContent);
            file_put_contents($path . "/" . $filename, $jsContent);
        }

        $this->appendFile($cachedir . "/" . $filename);

        ZFE_Util_Stopwatch::trigger(__METHOD__);

        return parent::toString($indent);
    }
}
