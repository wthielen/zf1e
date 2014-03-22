<?php

/**
 * The abstract minifier class that contains the common cache_dir option
 * and has a default minify() function that gets the content and returns 
 * it unchanged.
 */
class ZFE_Resources_Minifier_Abstract
{
    protected $_cacheDir;

    public function __construct($options) 
    {
        if (isset($options['cache_dir'])) $this->_cacheDir = $options['cache_dir'];
    }

    public function getCacheDir()
    {
        return $this->_cacheDir;
    }

    public function minify($content) {
        return $content;
    }
}
