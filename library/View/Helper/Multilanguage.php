<?php

class ZFE_View_Helper_Multilanguage extends Zend_View_Helper_Abstract
{
    const TYPE_SELECT = 'select';
    const TYPE_LIST = 'list';

    private $resource;
    private $plugin;
    private $type;

    /**
     * The Multi-language view helper
     */
    public function multilanguage($type = self::TYPE_SELECT)
    {
        $this->type = $type;

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $this->resource = $bootstrap->getPluginResource('Multilanguage');
        $this->plugin = $front->getPlugin('ZFE_Plugin_Multilanguage');

        return $this;
    }

    /**
     * To be able to call functions on the multilanguage helper itself
     */
    public function direct()
    {
        return $this;
    }

    /**
     * Convenience function to get the configured languages
     *
     * @return array
     */
    public function getLanguages()
    {
        if (null === $this->resource) return array();

        return $this->resource->getLanguages();
    }

    /**
     * Convenience function to get the current language
     *
     * @return string
     */
    public function getLanguage()
    {
        if (null === $this->resource) {
            $locale = new Zend_Locale();
            return $locale->getLanguage();
        }

        return $this->resource->getLanguage();
    }

    /**
     * Gets the MultiLanguage resource
     *
     * @return object
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Returns HTML for the view control to select a language from
     * the supported languages.
     *
     * If the Multi-language resource plugin is not used, it will
     * return an empty string.
     *
     * If the given type is unknown, it will return the select list
     */
    public function __toString()
    {
        if (null === $this->resource) return '';

        $fn = '_type' . ucfirst(strtolower($this->type));
        return method_exists($this, $fn) ? $this->$fn() : $this->_typeSelect();
    }

    /**
     * This function creates HTML for the select pull-down, with the
     * current language being selected. Its option entries have a data-url
     * attribute. The application needs to implement an onchange event
     * handler for this select, which should go to the URL given in the
     * data-url attribute.
     */
    private function _typeSelect()
    {
        $languages = $this->resource->getLanguages();
        $language = $this->resource->getLanguage();

        $html = '<select class="zfe_multilanguage">';
        foreach($languages as $key => $lang) 
        {
            $selected = $key == $language ? 'selected="selected"' : '';
            $url = htmlspecialchars($this->plugin->composeUrl($key));
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
    private function _typeList()
    {
        $languages = $this->resource->getLanguages();
        $language = $this->resource->getLanguage();

        $html = '<ul class="zfe_multilanguage">';
        foreach($languages as $key => $lang) 
        {
            $class = $key == $language ? 'zfe_selected' : '';
            $url = htmlspecialchars($this->plugin->composeUrl($key));
            $html .= "<li class=\"$class\"><a href=\"$url\">$lang</a></li>";
        }
        $html .= '</ul>';

        return $html;
    }
}
