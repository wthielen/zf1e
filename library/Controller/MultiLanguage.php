<?php

/**
 * ZFE_Controller_MultiLanguage
 *
 * This controller is multi-language-aware. It offers an array $i18nactions where the
 * developer can put in the action names of the pages that should have different view
 * scripts per language. These view scripts will then have the format:
 * <action name>-<language code>.phtml
 *
 * When it can not find the view script in any of the renderer's script paths, it will
 * fallback to the default language and pick that one.
 */
class ZFE_Controller_MultiLanguage extends ZFE_Controller_Base
{
    // The language variable
    protected $lang;

    // The array that holds the action names of multi-language-aware actions
    protected $i18nActions = array();

    public function init()
    {
        parent::init();

        $resource = ZFE_Environment::getResource('Multilanguage');
        $this->lang = $resource->getLanguage();
        if (is_null($this->lang)) $this->lang = $resource->getDefault();

        $this->view->lang = $this->lang;
    }

    public function postDispatch()
    {
        parent::postDispatch();

        $front = Zend_Controller_Front::getInstance();
        $mlPlugin = $front->getPlugin('ZFE_Plugin_Multilanguage');

        $resource = ZFE_Environment::getResource('Multilanguage');
        $lang = $resource->getLanguage();
        $default = $resource->getDefault();

        // Add alternate URLs for the translations
        $languages = $resource->getLanguages();
        unset($languages[$lang]);
        foreach($languages as $it_lang => $language) {
            // Allowing override, since translated pages might have different URLs (slug, ID)
            if (isset($this->customAlternateURL) && isset($this->customAlternateURL[$it_lang])) {
                $url = $this->customAlternateURL[$it_lang];
            } else {
                $url = $mlPlugin->composeUrl($it_lang);
            }

            $alt_lang = $it_lang;
            //https://support.google.com/webmasters/answer/189077?hl=en
            if($it_lang == 'zh_Hans') $alt_lang = 'zh-Hans';
            if($it_lang == 'zh_Hant') $alt_lang = 'zh-Hant';
            $this->view->headLink()->appendAlternate($url, null, '', array('hreflang' => $alt_lang));
        }

        // If the action is in the i18nActions variable, update the script name to render
        $action = $this->getRequest()->getActionName();
        if (in_array($action, $this->i18nActions)) {
            $viewRenderer = $this->getHelper('ViewRenderer');

            // Update the script with the current language
            $viewRenderer->setScriptAction($action . "-" . $lang);

            // Check if the script exists
            $paths = $this->view->getScriptPaths();
            $script = $viewRenderer->getViewScript();
            $exists = array_reduce($paths, function($ret, $path) use($script) {
                return $ret || file_exists($path . $script);
            }, false);

            // If the script does not exist, fallback to the default language
            // Its existence will be taken care of by the View class
            if (!$exists && $lang !== $default) {
                $viewRenderer->setScriptAction($action . "-" . $default);
            }
        }
    }
}
