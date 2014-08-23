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
    // The array that holds the action names of multi-language-aware actions
    protected $i18nActions = array();

    public function postDispatch()
    {
        $action = $this->getRequest()->getActionName();

        // If the action is in the i18nActions variable, update the script name to render
        if (in_array($action, $this->i18nActions)) {
            $resource = $this->getInvokeArg('bootstrap')->getPluginResource('Multilanguage');
            $viewRenderer = $this->getHelper('ViewRenderer');

            $lang = $resource->getLanguage();
            $default = $resource->getDefault();

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
