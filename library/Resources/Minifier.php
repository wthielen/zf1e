<?php

/**
 * An application plugin resource that tells the library what to do
 * to minify files.
 *
 * Currently it minifies CSS and JS, and they are dealt with in the
 * ZFE_Resources_Minifier_{Css, Js} classes.
 */
class ZFE_Resource_Minifier extends Zend_Application_Resource_ResourceAbstract
{
    private $css;
    private $js;

    public function init()
    {
        $options = $this->getOptions();
        if (isset($options['css'])) {
            $this->css = new ZFE_Resources_Minifier_Css($options['css']);
        }

        if (isset($options['js'])) {
            $this->js = new ZFE_Resources_Minifier_Js($options['js']);
        }
    }

    public function __get($key) {
        if (in_array($key, array('css', 'js'))) return $this->$key;

        return null;
    }
}
