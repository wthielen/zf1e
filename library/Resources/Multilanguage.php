<?php

class ZFE_Resource_Multilanguage extends Zend_Application_Resource_ResourceAbstract
{
    private $locale;
    private $language;

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
        
        // Throw exceptions if 'domain' or 'languages' is missing from the options
        if (!isset($options['domain'])) {
            throw new Zend_Application_Resource_Exception('Please specify main domain: resources.multilanguage.domain');
        }

        if (!isset($options['languages']) || count($options['languages']) == 0) {
            throw new Zend_Application_Resource_Exception('Please specify one or more supported languages for your application: resources.multilanguage.languages[]');
        }

        // Perform browser language detection and redirection if the main domain is accessed
        $domain = $_SERVER['SERVER_NAME'];
        if ($domain === $options['domain']) {
            $this->locale = $this->getBootstrap()->getResource('locale');
            if (null === $this->locale) $this->locale = new Zend_Locale();

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
            $subdomain = strtolower(str_replace('_', '-', $language));
            $proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off" ? 'https' : 'http';
            $redirect = $proto . "://" . $subdomain . "." . $options['domain'];
            $redirect .= $_SERVER['REQUEST_URI'];

            header('HTTP/1.1 302');
            header('Location: ' . $redirect);
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

        $this->language = $language;
    }

    /**
     * Returns the currently set language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Returns a list of languages, with their names in their respective 
     * languages
     */
    public function getLanguages()
    {
        $options = $this->getOptions();

        $ret = array();
        if (@is_array($options['languages'])) {
            foreach($options['languages'] as $lang) {
                $ret[$lang] = Zend_Locale_Data::getContent($lang, 'language', $lang);
            }
        }

        return $ret;
    }

    /**
     * Returns the default language
     */
    public function getDefault()
    {
        $options = $this->getOptions();

        return @is_array($options['languages']) ? $options['languages'][0] : null;
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
