<?php

/**
 * The DebugFilter resource class
 *
 * This class is used by ZFE_Core::dump() to determine whether the current session
 * can produce debug messages. It does that by checking the remote IP address of the
 * connecting client, and its User-Agent string.
 *
 * This makes it easier to debug live sites, where you don't want other people to see
 * your debug messages, but only you. If you work in an office with a shared IP 
 * address, you can use the User-Agent filter, so that your colleagues won't see your
 * debug messages.
 */
class ZFE_Resource_Debugfilter extends Zend_Application_Resource_ResourceAbstract
{
    private $options;
    private $request;

    /**
     * Initializes the resource and sets some default values.
     * Stores itself in the register for easy access.
     */
    public function init()
    {
        $this->options = $this->getOptions();
        if (!isset($this->options['allowAddress'])) $this->options['allowAddress'] = array();

        Zend_Registry::set('ZFE_DebugFilter', $this);
    }

    /**
     * Based on the IP and User-Agent filters, this function checks if debug messages can
     * be printed. Local IP addresses are by default allowed, but if defined, the User-Agent
     * check has the veto.
     */
    public function isAllowed()
    {
        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();

        $ipaddr = $request->getClientIp();

        return $this->userAgentAllowed($this->isLocal() || in_array($ipaddr, $this->options['allowAddress']));
    }

    /**
     * A private function to check if the remote client is using a local IP address.
     * Maybe this function should become a public function in the Core class.
     */
    private function isLocal()
    {
        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();
        $ipaddr = $request->getClientIp();

        return ZFE_Core::isLocal($ipaddr);
    }

    /**
     * If a User-Agent filter has been set, it checks whether the remote client's user agent
     * matches this filter.
     */
    private function userAgentAllowed($default)
    {
        if (!isset($this->options['allowUserAgent'])) return $default;

        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();

        $ua = $request->getHeader('User-Agent');
        return array_reduce($this->options['allowUserAgent'], function($u, $v) use ($ua) {
            return $u || strpos($ua, $v) !== false;
        }, false);
    }
}
