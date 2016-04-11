<?php

/**
 * ACQ_Mail class extends Zend_Mail and is suited to ACQ emails. It uses Zend_View
 * templates for the body text, and handles HTML and plain emails by template file name
 * convention.
 *
 * TODO: Add "inline images" option (http://stackoverflow.com/questions/1087933/how-to-send-an-email-with-inline-images-using-zend-framework)
 * TODO override send with send(true) optional param that clears all the previous send stuff (e.g. subject)
 */
class ZFE_Mail extends Zend_Mail
{
    protected $_view;
    protected $_layout;
    protected $_layoutScript;
    protected $_template;
    // protected $_language;
    // protected $_defaultLanguage;
    // protected $_version;
    // protected $_charset = 'UTF-8';

    protected $_options;

    static protected $_defaultVersion;

    public function __construct($options=array()) //Zend_Layout $layout, Zend_View_Interface $view, ?
    {
        // TODO remove these once we are passing these values in
        if (!isset($options['language'])) {
            $options['language'] = ZFE_Core::getLanguage();
        }
        if (!isset($options['defaultLanguage'])) { // passes
            $ml = Zend_Registry::get('ZFE_MultiLanguage');
            $options['defaultLanguage'] = $ml->getDefault();
        }
        if (!isset($options['layoutPath'])) {
            $options['layoutPath'] = APPLICATION_PATH . '/emails/layouts';
        }
        if (!isset($options['scriptPath'])) {
            $options['scriptPath'] = APPLICATION_PATH . '/emails/scripts';
        }
        // set defaults
        // TODO remove layoutPath, scriptPath, defaultLayout as these strictly be passed in
        $options = array_merge(array(
            'defaultLayout' => 'default',
            'charset' => 'UTF-8',
        ), $options);

        // check layoutPath has been set
        if (!isset($options['layoutPath'])) {
            throw new Exception('Layout path not set or not found');
        }

        // check scriptPath has been set
        if (!isset($options['scriptPath'])) {
            throw new Exception('Script path not set or not found');
        }


        // set the layout for the emails. this will be our lovely html header and
        // footers making our emails look warm and fuzzy :)
        $this->_layout = new Zend_Layout();
        $this->_layout->setLayoutPath($options['layoutPath']);
        if (isset($options['defaultLayout'])) { // TODO is this optional? should it be?
            $this->_layoutScript = $options['defaultLayout'];
            $this->_layout->setLayout($this->_layoutScript);
        }

        // set View
        $this->_view = new Zend_View();
        $this->_view->setScriptPath($options['scriptPath']);

        // set options
        $this->_options = $options;

        // $this->_view = new Zend_View();
        //
        // // set the base directory of where our emails scripts are located
        // $this->_view->setScriptPath(APPLICATION_PATH . "/emails/scripts");
        //
        // $this->_view->addHelperPath(ZFE_Environment::getLibraryPath() . "/View/Helper", 'ZFE_View_Helper');
        // $this->_view->addHelperPath(ZFE_Environment::getLibraryPath() . "/View/Helper", 'ACQ_View_Helper');
        //
        // $module = ZFE_Environment::getModuleName();
        // if ($module !== 'default') {
        //     $cls = ucfirst(strtolower($module)) . '_View_Helper';
        //     $this->_view->addHelperPath(ZFE_Environment::getModulePath() . "/views/helpers", $cls);
        // }
        //
        // $this->_layout = new Zend_Layout();
        // $this->_layout->setLayoutPath(APPLICATION_PATH . "/emails/layouts");
        // $this->_layoutScript = 'default';
        // $this->_layout->setLayout($this->_layoutScript);
        //
        // $this->_version = self::$_defaultVersion;
        //
        // $this->_options['language'] = ZFE_Core::getLanguage();
    }

    public function setLayout($layout)
    {
        $this->_layoutScript = $layout;

        return $this;
    }

    public function setTemplate($template)
    {
        $this->_template = $template;

        return $this;
    }

    public function setTemplateData($data)
    {
        $this->_view->assign($data);

        return $this;
    }

    public function setLanguage($language)
    {
        $this->_options['language'] = $language;

        return $this;
    }

    public function setVersion($version)
    {
        $this->_version = $version;

        return $this;
    }

    /**
     * To allow this class to be used as a singleton we perhaps need an option
     * to reset the params prior to mailing
     */
    public function resetParams()
    {
        $this->clearSubject();
        $this->clearRecipients();
        $this->clearFrom();

        // clear the body
        $this->_bodyText = false;

        return $this;
    }

    static public function setDefaultVersion($version)
    {
        self::$_defaultVersion = $version;
    }

    /**
     * This will call Zend_View but as ACQ_Mail has the option to render with
     * Zend_View we will ensure that body texts have been set the Zend_Mail way
     * before calling Zend_Mail::send
     *
     * There are several cases: text-only, html-only, text-and-html. The way this
     * is implemented is to cover for all cases.
     *
     * @param Zend_Mail_Transport? $transport The transport option (optional)
     */
    public function send($transport = null)
    {
        // Check if we have text and/or HTML already in the Zend_Mail (parent) object
        $gotText = $this->getBodyText();
        $gotHtml = $this->getBodyHtml();

        // UPDATE: Martyn: Seems we're double checking for body texts (here, and in getText), so i've commented stuff out
        // maybe needs checking/testing but I figured this could be simplified
        // UPDATE: Wouter: Yes I know. It's shouganai.

        // We don't have text => generate it using our templates the ACQ_Mail way
        if ($gotText === false) {
            $text = $this->getText();

            // Since we are trying to generate it the ACQ_Mail way, it shouldn't be empty
            if (empty($text)) {
                throw new Exception("Empty text template for {$this->_template}. Could not send e-mail.");
            }

            $this->setBodyText($text);
        }

        // We don't have HTML => generate it using our templates the ACQ_Mail way
        if ($gotHtml === false) {
            $html = $this->getHtml();

            // Since we are trying to generate it the ACQ_Mail way, it shouldn't be empty
            if (empty($html)) {
                throw new Exception("Empty HTML template for {$this->_template}. Could not send e-mail.");
            }

            $this->setBodyHtml($html);
        }

        parent::send($transport);
    }

    /**
     * Get the rendered plain text by rendering the view with layout and data
     *
     * @return string Rendered plain text for email body
     */
    public function getText()
    {
        // Check if body text has already been set via Zend_Mail (the parent)
        $text = $this->getBodyText();

        if ($text === false) {
            $text = "";
            $textScript = $this->_findTextScript();

            if ($textScript) {
                // clone Zend_View as we will use it twice (plain text template, and html template)
                // and set the view parameters
                $view = clone $this->_view;
                $view->language = $this->_options['language'];

                // render the email template with Zend_View to generate the body text
                $text = $view->render($textScript);

            }
        }

        return $text;
    }

    /**
     * Get the rendered HTML text by rendering the view with layout and data
     *
     * @return string Rendered html for email body
     */
    public function getHtml()
    {
        $html = $this->getBodyHtml();

        if ($html === false) {
            $html = "";
            $htmlScript = $this->_findHtmlScript();

            if ($htmlScript) {
                // clone Zend_View as we will use it twice (plain text template, and html template)
                // and set the view parameters
                $view = clone $this->_view;
                $view->language = $this->_options['language'];

                // Set the layout based on available versions
                if (!empty($this->_version) && $this->_layoutExists($this->_layoutScript . '-' . $this->_version)) {
                    $this->_layout->setLayout($this->_layoutScript . '-' . $this->_version);
                } else {
                    $this->_layout->setLayout($this->_layoutScript);
                }

                // Assign view and view variables into layout the layout
                // if layout has not been set, a default layout will be used (see init())
                $this->_layout->setView($view);
                $this->_layout->content = $view->render($htmlScript);

                // render the email template with Zend_View to generate the body text
                $html = $this->_layout->render();
            }
        }

        return $html;
    }

    /**
     * Returns true if the script exists in the mail layout path.
     *
     * A bit of a hack, it would be nice if Zend_Layout had such a function. Candidate for ACQ_Layout...
     *
     * @param string $script layout script name
     * @return bool true if the script exists in the path
     */
    private function _layoutExists($script) {
        $dummyView = new Zend_View();
        $dummyView->addScriptPath(APPLICATION_PATH . "/emails/layouts");

        return ($dummyView->getScriptPath($script . '.phtml') == true);
    }

    /**
     * Get the plain text template, return the most preferable (e.g. the current language)
     * @return string Preferred script path
     */
    protected function _findTextScript()
    {
        // $ml = Zend_Registry::get('ZFE_MultiLanguage');

        // put each possible script in the $scripts array in order of
        // peference (e.g. first look for current languages, then default, ..)
        $textScript = false;
        $scripts = array();
        if (!empty($this->_version)) {
            $scripts[] = $this->_template . '-' . $this->_version . '-' . $this->_options['language'] . '.phtml';
            $scripts[] = $this->_template . '-' . $this->_version . '-' . $this->_options['defaultLanguage'] . '.phtml';
            $scripts[] = $this->_template . '-' . $this->_version . '.phtml';
        }
        $scripts[] = $this->_template . '-' . $this->_options['language'] . '.phtml';
        $scripts[] = $this->_template . '-' . $this->_options['defaultLanguage'] . '.phtml';
        $scripts[] = $this->_template . '.phtml';

        // loop through each script in order of preference, set as the
        // first script that exists. Zend_View::getScriptPath will return
        // false/null if the file doesn't exist(?)
        foreach($scripts as $script) {
            if ($this->_view->getScriptPath($script)) {
                $textScript = $script;
                break;
            }
        }

        return $textScript;
    }

    /**
     * Get the html template, return the most preferable (e.g. the current language)
     * @return string Preferred script path
     */
    protected function _findHtmlScript()
    {
        // $ml = Zend_Registry::get('ZFE_MultiLanguage');

        // put each possible script in the $scripts array in order of
        // peference (e.g. first look for current languages, then default, ..)
        $htmlScript = false;
        $scripts = array();
        if (!empty($this->_version)) {
            $scripts[] = $this->_template . '-' . $this->_version . '-' . $this->_options['language'] . '.html.phtml';
            $scripts[] = $this->_template . '-' . $this->_version . '-' . $this->_options['defaultLanguage'] . '.html.phtml';
            $scripts[] = $this->_template . '-' . $this->_version . '.html.phtml';
        }
        $scripts[] = $this->_template . '-' . $this->_options['language'] . '.html.phtml';
        $scripts[] = $this->_template . '-' . $this->_options['defaultLanguage'] . '.html.phtml';
        $scripts[] = $this->_template . '.html.phtml';

        // loop through each script in order of preference, set as the
        // first script that exists. Zend_View::getScriptPath will return
        // false/null if the file doesn't exist(?)
        foreach($scripts as $script) {
            if ($this->_view->getScriptPath($script)) {
                $htmlScript = $script;
                break;
            }
        }

        return $htmlScript;
    }
}
