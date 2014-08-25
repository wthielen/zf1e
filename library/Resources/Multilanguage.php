<?php

class ZFE_Resource_Multilanguage extends Zend_Application_Resource_ResourceAbstract
{
    private $language;
    private $translate;

    private static $_adapterExt = array(
        'gettext' => '.mo',
        'csv' => '.csv'
    );

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

        // Register Multilanguage plugin
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('frontController');
        $front = $bootstrap->getResource('frontController');
        $front->registerPlugin(new ZFE_Plugin_Multilanguage());

        Zend_Registry::set('ZFE_MultiLanguage', $this);
    }

    /**
     * Zend_Translate initializer
     */
    public function initTranslate()
    {
        $options = $this->getOptions();

        if (isset($options['adapter'])) {
            if (!isset($options['contentPath'])) {
                throw new Zend_Application_Resource_Exception('Please specify the content path where your translation sources are: resources.multilanguage.contentPath');
            }

            $adapter = $options['adapter'];
            if (!isset(self::$_adapterExt[$adapter])) {
                throw new Zend_Application_Resource_Exception('Unknown adapter for translation: ' . $adapter);
            }

            $path = $options['contentPath'];
            $config = array(
                'adapter' => $adapter,
                'content' => $path . DIRECTORY_SEPARATOR . $this->getLanguage() . self::$_adapterExt[$adapter]
            );

            if (!file_exists($config['content'])) {
                $config['content'] = $path . DIRECTORY_SEPARATOR . $this->getDefault() . self::$_adapterExt[$adapter];
            }

            $this->translate = new Zend_Translate($config);
        }
    }

    /**
     * Sets the language
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
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
     * The default translate function
     *
     * It supports variable arguments, so that if the text contains sprintf placeholders,
     * they will be replaced by the arguments passed.
     */
    public function _($messageId)
    {
        if (null === $this->translate) {
            return $messageId;
        }

        $txt = $this->translate->translate($messageId);

        $args = func_get_args();
        if (count($args) == 1) return $txt;

        array_shift($args);
        return vsprintf($txt, $args);
    }

    /**
     * The translate function for plurals
     *
     * May add variable argument support, but then it would be unclear which argument
     * would decide the outcome. It could be set to take the first argument for that...
     */
    public function _n($messageId, $pluralId, $n)
    {
        if (null === $this->translate) {
            return $messageId;
        }

        $txt = $this->translate->translate(array(
            $messageId, $pluralId, $n
        ));

        return sprintf($txt, $n);
    }

    /**
     * Context translation
     *
     * This supports variable arguments to be put in the placeholders of the translated
     * text.
     */
    public function _x($messageId, $ctxt)
    {
        if (null === $this->translate) {
            return $messageId;
        }

        $txt = $this->translate->translate($ctxt . chr(4) . $messageId);

        $args = func_get_args();
        if (count($args) == 2) return $txt;

        array_shift($args);
        return vsprintf($txt, $args);
    }

    /**
     * Plural translation with context
     */
    public function _nx($messageId, $pluralId, $n, $ctxt)
    {
        if (null === $this->translate) {
            return $messageId;
        }

        $txt = $this->translate->translate(array(
            $ctxt . chr(4) . $messageId,
            $ctxt . chr(4) . $pluralId,
            $n
        ));

        return sprintf($txt, $n);
    }
}
