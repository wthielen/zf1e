<?php

/**
 * The abstract minifier class that contains the common cache_dir option
 * and has a default minify() function that gets the content and returns 
 * it unchanged.
 */
class ZFE_Resources_Minifier_Abstract
{
    protected $_cacheDir;
    protected $_minify = true;
    protected $_bundle = true;

    public function __construct($options) 
    {
        if (isset($options['cache_dir'])) $this->_cacheDir = $options['cache_dir'];
        if (isset($options['minify'])) $this->_minify = $options['minify'] ? true : false;
        if (isset($options['bundle'])) $this->_bundle = $options['bundle'] ? true : false;
    }

    public function getCacheDir()
    {
        return $this->_cacheDir;
    }

    public function minify($content)
    {
        return $content;
    }

    public function doBundle()
    {
        return $this->_bundle;
    }
}
