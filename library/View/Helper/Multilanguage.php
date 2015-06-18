<?php

class ZFE_View_Helper_Multilanguage extends Zend_View_Helper_Abstract
{
    const TYPE_SELECT = 'select';
    const TYPE_LIST = 'list';

    protected $resource;
    protected $plugin;
    protected $type;

    public function init()
    {
        $front = false;
        if (php_sapi_name() == 'cli') {
            $app = Zend_Registry::get('CliApplication');
            $bootstrap = $app->getBootstrap();
        } else {
            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');
        }

        $this->resource = $bootstrap->getPluginResource('Multilanguage');

        if ($front) $this->plugin = $front->getPlugin('ZFE_Plugin_Multilanguage');
    }

    /**
     * The Multi-language view helper
     */
    public function multilanguage($type = self::TYPE_SELECT)
    {
        $this->type = $type;

        return $this->direct();
    }

    /**
     * To be able to call functions on the multilanguage helper itself
     */
    public function direct()
    {
        $this->init();

        return $this;
    }

    /**
     * Convenience function to get the configured languages
     *
     * @return array
     */
    public function getLanguages($translated = false)
    {
        if (null === $this->resource) return array();

        return $this->resource->getLanguages($translated);
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

    public function _($messageId)
    {
        if (null === $this->resource) return $messageId;

        $args = func_get_args();
        return call_user_func_array(array($this->resource, "_"), $args);
    }

    public function _n($messageId, $pluralId, $n)
    {
        if (null === $this->resource) return $messageId;

        return $this->resource->_n($messageId, $pluralId, $n);
    }

    public function _x($messageId, $ctxt)
    {
        if (null === $this->resource) return $ctxt . $messageId;

        $args = func_get_args();
        return call_user_func_array(array($this->resource, "_x"), $args);
    }

    public function _nx($messageId, $pluralId, $n, $ctxt)
    {
        if (null === $this->resource) return $ctxt . $messageId;

        return $this->resource->_nx($messageId, $pluralId, $n, $ctxt);
    }

    /**
     * This function creates HTML for the select pull-down, with the
     * current language being selected. Its option entries have a data-url
     * attribute. The application needs to implement an onchange event
     * handler for this select, which should go to the URL given in the
     * data-url attribute.
     */
    protected function _typeSelect()
    {
        $languages = $this->resource->getLanguages(true);
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
    protected function _typeList()
    {
        $languages = $this->resource->getLanguages(true);
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
