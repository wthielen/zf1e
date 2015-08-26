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

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        // Initialize the variables
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');

        $this->locale = $bootstrap->getResource('locale');
        if (null === $this->locale) $this->locale = new Zend_Locale();

        $this->resource = ZFE_Environment::getResource('Multilanguage');
        $options = $this->resource->getOptions();
        $language = $this->getBrowserLanguage();

        // If a domain is given, perform subdomain-based language detection
        if (isset($options['domain'])) {
            $domain = $request->getHttpHost();

            // If the main domain is accessed, use the browser language and
            // redirect to that subdomain
            if ($domain === $options['domain']) {
                // Perform 302 redirect
                header('HTTP/1.1 302');
                header('Location: ' . $this->composeUrl($language));
                exit();
            }

            // If it is not an IP address, extract the language from the domain, and store it
            if (!ZFE_Core::isIpAddress($domain)) {
                $subdomain = strtolower(str_replace('.' . $options['domain'], '', $domain));
                $parts = explode('-', $subdomain);
                $language = $parts[0];
                if (isset($parts[1])) $language .= '_' . ucfirst($parts[1]);
            }
        }

        // Store the language in the resource
        // This also initializes the translation resource
        $this->resource->setLanguage($language);
    }

    public function getBrowserLanguage()
    {
        $script = null;
        $options = $this->resource->getOptions();
        $language = $this->locale->getLanguage();

        // Do something about languages with specific scripts
        if ("zh" === $language) $script = $this->_detectChineseScript();

        if ($script) $language .= '_' . ucfirst(strtolower($script));

        // Pick the default language if the given language is not supported
        if (!in_array($language, $options['languages'])) {
            $language = $options['languages'][0];
        }

        return $language;
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

        $url = $proto . "://" . $_SERVER['HTTP_HOST'] . $path;
        if (isset($options['domain'])) {
            $url = $proto . "://" . $subdomain . "." . $options['domain'] . $path;
        }

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
