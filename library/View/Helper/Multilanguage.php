<?php

class ZFE_View_Helper_Multilanguage extends Zend_View_Helper_Abstract
{
    const TYPE_SELECT = 'select';
    const TYPE_LIST = 'list';

    private $resource;

    /**
     * The Multi-language view helper
     *
     * Returns HTML for the view control to select a language from
     * the supported languages.
     *
     * If the Multi-language resource plugin is not used, it will
     * return an empty string.
     */
    public function multilanguage($type = self::TYPE_SELECT)
    {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $this->resource = $bootstrap->getPluginResource('Multilanguage');

        if (null === $this->resource) return "";

        $fn = '_' . strtolower($type);
        if (!method_exists($this, $fn)) {
            throw new Exception("Requested type $type not available in " . get_class($this));
        }

        return $this->$fn();
    }

    /**
     * This function creates HTML for the select pull-down, with the
     * current language being selected. Its option entries have a data-url
     * attribute. The application needs to implement an onchange event
     * handler for this select, which should go to the URL given in the
     * data-url attribute.
     */
    private function _select()
    {
        $languages = $this->resource->getLanguages();
        $language = $this->resource->getLanguage();

        $html = '<select class="zfe_multilanguage">';
        foreach($languages as $key => $lang) 
        {
            $selected = $key == $language ? 'selected="selected"' : '';
            $url = htmlspecialchars($this->resource->composeUrl($key));
            $html .= "<option value=\"$key\" data-url=\"$url\" $selected>$lang</option>";
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * This function creates HTML for an unordered list with the languages
     * listed. The current language's list element has a special class, so
     * the application can give it a different style in the view with CSS.
     * The list elements contain a link which will go to the corresponding
     * URL for this language, so no JavaScript implementation is needed.
     */
    private function _list()
    {
        $languages = $this->resource->getLanguages();
        $language = $this->resource->getLanguage();

        $html = '<ul class="zfe_multilanguage">';
        foreach($languages as $key => $lang) 
        {
            $class = $key == $language ? 'zfe_selected' : '';
            $url = htmlspecialchars($this->resource->composeUrl($key));
            $html .= "<li class=\"$class\"><a href=\"$url\">$lang</a></li>";
        }
        $html .= '</ul>';

        return $html;
    }
}
