<?php

class ZFE_Resource_Multilanguage extends Zend_Application_Resource_ResourceAbstract
{
    private $locale;

    /**
     * Resource initializer
     *
     * To use this resource, please add the following configuration to your
     * config.ini:
     *
     * resources.multilanguage.domain = "example.org" // Your main domain
     * resources.multilanguage.languages[] = "en" // Your default language comes first
     * resources.multilanguage.languages[] = "de" // Your other supported languages
     *
     * It will throw an exception if either domain or languages[] is missing.
     *
     * This resource will not be used if you don't use any resources.multilanguage.*
     * configuration in your config.ini.
     */
    public function init()
    {
        $options = $this->getOptions();
        if (null === $options) return null;

        $requestDomain = $_SERVER['SERVER_NAME'];
        
        // Throw exceptions if 'domain' or 'languages' is missing from the options
        if (!isset($options['domain'])) {
            throw new Zend_Application_Resource_Exception('Please specify main domain: resources.multilanguage.domain');
        }

        if (!isset($options['languages']) || count($options['languages']) == 0) {
            throw new Zend_Application_Resource_Exception('Please specify one or more supported languages for your application: resources.multilanguage.languages[]');
        }

        // Perform browser language detection and redirection if the main domain is accessed
        if ($requestDomain === $options['domain']) {
            $this->locale = $this->getBootstrap()->getResource('locale');
            if (null === $this->locale) $this->locale = new Zend_Locale();

            $script = null;
            $language = $this->locale->getLanguage();

            // Do something about languages with specific scripts
            if ("zh" === $language) $script = $this->_detectChineseScript();

            $iso639 = $language;
            if ($script) $iso639 .= '_' . ucfirst(strtolower($script));

            // Pick the first language as default if it is not listed as
            // a supported language
            if (!in_array($iso639, $options['languages'])) {
                $iso639 = $options['languages'][0];
            }

            // Perform 302 redirect
            $subdomain = strtolower(str_replace('_', '-', $iso639));
            $proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off" ? 'https' : 'http';
            $redirect = $proto . "://" . $subdomain . "." . $options['domain'];
            $redirect .= $_SERVER['REQUEST_URI'];

            header('HTTP/1.1 302');
            header('Location: ' . $redirect);
            exit();
        }
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
