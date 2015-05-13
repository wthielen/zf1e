<?php

class ZFE_Resource_Multilanguage extends Zend_Application_Resource_ResourceAbstract
{
    private $language;

    private $translate;
    private $fallback;

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
        
        // Throw exceptions if 'languages' is missing from the options
        if (!isset($options['languages']) || count($options['languages']) == 0) {
            throw new Zend_Application_Resource_Exception('Please specify one or more supported languages for your application: resources.multilanguage.languages[]');
        }

        // Register Multilanguage plugin
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');
        $front = $bootstrap->getResource('FrontController');
        $front->registerPlugin(new ZFE_Plugin_Multilanguage(), 500);

        Zend_Registry::set('ZFE_MultiLanguage', $this);
    }

    /**
     * Zend_Translate initializer
     *
     * Initializes the translator, and if necessary prepares a fallback
     * translator as well using the default language, in case the message 
     * IDs have not been translated in the current language.
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

            $fallback_config = array(
                'adapter' => $adapter,
                'content' => $path . DIRECTORY_SEPARATOR . $this->getDefault() . self::$_adapterExt[$adapter]
            );

            if (!file_exists($config['content'])) {
                $this->translate = new Zend_Translate($fallback_config);
                $this->fallback = null;
            } else {
                $this->translate = new Zend_Translate($config);
                if ($this->getDefault() != $this->getLanguage()) {
                    $this->fallback = new Zend_Translate($fallback_config);
                }
            }
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
    public function getLanguages($translated = false)
    {
        $options = $this->getOptions();

        $ret = array();
        if (@is_array($options['languages'])) {
            foreach($options['languages'] as $lang) {
                try {
                    $str = Zend_Locale_Data::getContent($translated ? $lang : $this->language, 'language', $lang);
                    $first = mb_strtoupper(mb_substr($str, 0, 1));
                    $ret[$lang] = $first . mb_substr($str, 1);
                } catch (Exception $e) {
                }
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
     *
     * If the given message ID has not been translated, and there is a fallback translator,
     * then it will be translated by the fallback translator.
     */
    public function _($messageId)
    {
        if (null === $this->translate) {
            return $messageId;
        }

        $txt = $this->translate->translate($messageId);
        if ($txt == $messageId && !is_null($this->fallback)) $txt = $this->fallback->translate($messageId);

        $args = func_get_args();
        if (count($args) == 1) return $txt;

        array_shift($args);

        // Check the number of levels in the $args array
        $test = current($args);
        $args = is_array($test) ? $test : $args;

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
        if ($txt == $messageId && !is_null($this->fallback)) {
            $txt = $this->fallback->translate(array(
                $messageId, $pluralId, $n
            ));
        }

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

        $msgId = $ctxt . chr(4) . $messageId;
        $txt = $this->translate->translate($msgId);
        if ($txt == $msgId && !is_null($this->fallback)) $txt = $this->fallback->translate($msgId);

        $args = func_get_args();
        if (count($args) == 2) return $txt;

        array_shift($args);
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

        $msgId = $ctxt . chr(4) . $messageId;
        $plrId = $ctxt . chr(4) . $pluralId;
        $txt = $this->translate->translate(array(
            $msgId, $plrId, $n
        ));
        if ($txt == $msgId && !is_null($this->fallback)) {
            $txt = $this->fallback->translate(array(
                $msgId, $plrId, $n
            ));
        }

        return sprintf($txt, $n);
    }
}
