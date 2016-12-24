<?php

/**
 * An extended view helper for adding JS files. If the JS minifier resource is set up
 * it will combine the files, minify them, and store them in the cache directory as
 * configured by the minifier resource.
 */
class ZFE_View_Helper_HeadScript extends Zend_View_Helper_HeadScript
{
    protected $_minifier = false;

    protected $doNotBundle = array();

    /**
     * The constructor checks if the minifier resource has been set up, and gets the
     * CSS minifier from the resource.
     */
    public function __construct()
    {
        parent::__construct();

        $resource = ZFE_Environment::getResource('minifier');
        if ($resource) $this->_minifier = $resource->js;
    }

    public function toString($indent = null)
    {
        // Return early if no minifier has been set up
        if (false === $this->_minifier || !$this->_minifier->doBundle()) {
            return parent::toString($indent);
        }

        $container = $this->getContainer();
        $container->ksort();

        $items = $container->getArrayCopy();

        // Collect JS files
        $compressable = array();
        foreach($items as $key => $item) {
            if (isset($item->attributes['src'])) {
                $src = $item->attributes['src'];

                $file = basename($src);
                if (in_array($file, $this->doNotBundle)) continue;

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

        // check directory exists. if not, create it
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }

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
                    $jsContent .= file_get_contents($file) . PHP_EOL;
                }
            }

            $jsContent = $this->_minifier->minify($jsContent);
            file_put_contents($path . "/" . $filename, $jsContent);
        }

        //Some scripts should be before the bundle, some should be after... Some should even be inbetween... Need something smarter.
        $this->prependFile($cachedir . "/" . $filename);

        return parent::toString($indent);
    }
}
