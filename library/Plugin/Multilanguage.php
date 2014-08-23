<?php

/**
 * ZFE_Plugin_Multilanguage
 *
 * This plugin takes care of language detection and domain redirection during the
 * route startup phase.
 */
class ZFE_Plugin_Multilanguage extends Zend_Controller_Plugin_Abstract
{
    private $resource;
    private $locale;

    public function routeStartup($request)
    {
        // Initialize the variables
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');

        $this->resource = $bootstrap->getPluginResource('Multilanguage');
        $this->locale = $bootstrap->getResource('locale');
        if (null === $this->locale) $this->locale = new Zend_Locale();

        // Perform browser language detection and redirection if the main domain is accessed
        $options = $this->resource->getOptions();
        $domain = $request->getHttpHost();
        if ($domain === $options['domain']) {
            $script = null;
            $language = $this->locale->getLanguage();

            // Do something about languages with specific scripts
            if ("zh" === $language) $script = $this->_detectChineseScript();

            if ($script) $language .= '_' . ucfirst(strtolower($script));

            // Pick the first language as default if it is not listed as
            // a supported language
            if (!in_array($language, $options['languages'])) {
                $language = $options['languages'][0];
            }

            // Perform 302 redirect
            header('HTTP/1.1 302');
            header('Location: ' . $this->composeUrl($language));
            exit();
        }

        // Extract the language from the domain, and store it
        $subdomain = strtolower(str_replace('.' . $options['domain'], '', $domain));
        $parts = explode('-', $subdomain);
        $language = $parts[0];
        if (isset($parts[1])) $language .= '_' . ucfirst($parts[1]);

        // Pick the default language if the given language is not supported
        if (!in_array($language, $options['languages'])) {
            $language = $options['languages'][0];
        }

        // Store the language in the resource
        $this->resource->setLanguage($language);
    }

    /**
     * Creates the URL to load the given path in the given language.
     * If no path is given, the current page's URL is used via
     * $_SERVER['REQUEST_URI'].
     */
    public function composeUrl($language, $path = null)
    {
        $options = $this->resource->getOptions();
        if (is_null($path)) $path = $_SERVER['REQUEST_URI'];

        $subdomain = strtolower(str_replace('_', '-', $language));
        $proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off" ? 'https' : 'http';
        $url = $proto . "://" . $subdomain . "." . $options['domain'] . $path;

        return $url;
    }

    /**
     * An helper function to return the script of the Chinese language used
     * based on the region. If no region is known, Simplified will be assumed.
     *
     * Simplified: Mainland China, Singapore
     * Traditional: Taiwan, Hongkong, Macau
     */
    private function _detectChineseScript()
    {
        $region = $this->locale->getRegion();

        $regionToScript = array(
            'TW' => 'Hant',
            'HK' => 'Hant',
            'MO' => 'Hant',
            'CN' => 'Hans',
            'SG' => 'Hans'
        );

        return isset($regionToScript[$region]) ? $regionToScript[$region] : 'Hans';
    }
}
