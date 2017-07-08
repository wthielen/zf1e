<?php

/**
 * An extended view helper for adding CSS files. If the CSS minifier resource is set up
 * it will combine the files, minify them, and store them in the cache directory as
 * configured by the minifier resource.
 */
class ZFE_View_Helper_HeadLink extends Zend_View_Helper_HeadLink
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
        if ($resource) $this->_minifier = $resource->css;
    }

    public function toString($indent = null)
    {
        // Return early if no minifier has been set up
        if (false === $this->_minifier || !$this->_minifier->doBundle()) {
            return parent::toString($indent);
        }

        ZFE_Util_Stopwatch::trigger(__METHOD__);
        $container = $this->getContainer();
        $container->ksort();

        $items = $container->getArrayCopy();

        // Collect CSS files
        $compressable = array();
        foreach($items as $key => $item) {
            if ($item->rel == 'stylesheet' &&
                $item->type == 'text/css' &&
                !$item->conditionalStylesheet) {
                    $file = basename($item->href);
                    if (in_array($file, $this->doNotBundle)) continue;

                    if (ZFE_Util_String::startsWith($item->href, "http://")) continue;
                    if (ZFE_Util_String::startsWith($item->href, "https://")) continue;
                    if (ZFE_Util_String::startsWith($item->href, "//")) continue;

                    if (!isset($compressable[$item->media])) {
                        $compressable[$item->media] = array();
                    }

                    $compressable[$item->media][] = $item;
                    unset($items[$key]);
                }
        }
        $container->exchangeArray($items);

        // Collect current data of the CSS files
        $hashes = array();
        $mtimes = array();
        foreach($compressable as $media => $items) {
            $hash = '';
            $_mtimes = array();
            foreach($items as $item) {
                $file = $_SERVER['DOCUMENT_ROOT'] . $item->href;

                if (Zend_Loader::isReadable($file)) {
                    $hash .= ':' . $item->href;
                    $_mtimes[] = filemtime($file);
                }
            }
            $hashes[$media] = $hash;
            $mtimes[$media] = $_mtimes;
        }

        // Check if the original CSS files have been updated since the
        // last minification
        foreach($hashes as $media => $hash) {
            $latestChange = max($mtimes[$media]);
            $filename = sha1($media . $hash . $latestChange) . ".css";

            $cachedir = $this->_minifier->getCacheDir();
            $path = $_SERVER['DOCUMENT_ROOT'] . $cachedir;

            // check directory exists. if not, create it
            if (!file_exists($path)) {
                mkdir($path, 0775, true);
            }

            //Since the latestChange is part of the filename hash, any change in the file
            //will result in a different name for the bundle. If the bundle exist, then it
            //is up to date.
            $regenerate = !file_exists($path . "/" . $filename);
            //Sadly this means there will be many outdated files. The cache folder should
            //be cleaned periodically.

            // If any CSS file in this media group has been updated since the last
            // minification, collect the content again, and store it in the cached version
            if ($regenerate) {
                //error_log('--- CSS bundling (' . $media . $hash . $latestChange . ') ---'); // for monitoring
                $cssContent = '';
                foreach($compressable[$media] as $item) {
                    $file = $_SERVER['DOCUMENT_ROOT'] . $item->href;
                    if (Zend_Loader::isReadable($file)) {
                        $cssContent .= file_get_contents($file) . PHP_EOL;
                    }
                }

                $cssContent = $this->_minifier->minify($cssContent);
                file_put_contents($path . "/" . $filename, $cssContent);
            }

            $this->appendStylesheet($cachedir . "/" . $filename . '?v=' . $latestChange, $media);
        }

        ZFE_Util_Stopwatch::trigger(__METHOD__);

        return parent::toString($indent);
    }
}
