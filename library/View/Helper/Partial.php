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
 *
 * If the given file name of the partial is for example partial.phtml, then it will try to find and
 * use partial-<lang>.phtml.
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

            // Use the view's language if set, otherwise the resource's language
            $lang = isset($this->view->language) ? $this->view->language : $resource->getLanguage();
            $default = $resource->getDefault();

            // To support multiple extensions, gather the extensions in an array
            // This supports e.g. .ajax.phtml and .html.phtml, etc.
            $ext_array = array();
            $name = $origname;
            while (($x = pathinfo($name, PATHINFO_EXTENSION)) != "") {
                array_unshift($ext_array, $x);
                $name = pathinfo($name, PATHINFO_FILENAME);
            }
            $ext = implode(".", $ext_array);
            $name = substr_replace($origname, "-" . $lang, strrpos($origname, $ext) - 1, 0);

            $exists = array_reduce($paths, function($ret, $path) use($name) {
                return $ret || file_exists($path . $name);
            }, false);

            if (!$exists && $lang !== $default) {
                $name = substr_replace($origname, "-" . $default, strrpos($origname, $ext) - 1, 0);
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
