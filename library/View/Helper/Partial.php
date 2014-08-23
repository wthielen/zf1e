<?php

/**
 * ZFE_View_Helper_Partial
 *
 * A multi-language aware view partial.
 *
 * If the Multilanguage resource is used, then it will insert a language code in the partial's file
 * name to see if translated versions of the partial exist. If it does not find a partial for the
 * current language, it falls back on the default language. If it still fails to find one for the
 * default language, it restores the partial's name to what was passed in the argument.
 **/
class ZFE_View_Helper_Partial extends Zend_View_Helper_Partial
{
    public function partial($origname = null, $module = null, $model = null)
    {
        if (0 == func_num_args()) {
            return $this;
        }

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $resource = $bootstrap->getPluginResource('Multilanguage');

        if (null !== $resource) {
            $paths = $this->view->getScriptPaths();

            $lang = $resource->getLanguage();
            $default = $resource->getDefault();

            $ext = pathinfo($origname, PATHINFO_EXTENSION);
            $name = substr_replace($origname, "-" . $lang, strpos($origname, $ext) - 1, 0);

            $exists = array_reduce($paths, function($ret, $path) use($name) {
                return $ret || file_exists($path . $name);
            }, false);

            if (!$exists && $lang !== $default) {
                $name = substr_replace($origname, "-" . $default, strpos($origname, $ext) - 1, 0);
                $exists = array_reduce($paths, function($ret, $path) use($name) {
                    return $ret || file_exists($path . $name);
                }, false);
            }

            if (!$exists) $name = $origname;
        } else {
            $name = $origname;
        }

        return parent::partial($name, $module, $model);
    }
}
