<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Init plugin to initialize and start a session
 */
final class Plugin_Init_Session implements Iface_Plugin_Init
{
    /**
     * Initializes the session
     *
     * Configuration file:
     *   system->session_timeout : number of seconds before session timeouts
     *     format: integer
     *     default: 0 (no timeout)
     *     required: no
     *
     * @param Utils_Config $config the configuration settings
     * @param string $namespace the application namespace
     * @param string $request the requested page
     * @post The session namespace is registered in the Zend_Registry as 'session'.
     */
    public function process(Utils_Config $config, $namespace, $request)
    {
        Zend_Session::start();
        // true to avoid further instantiation. Use the Registry to manipulate the session.
        $session = new Zend_Session_Namespace($namespace, true);
        if (!is_null($config->system->session_timeout)) {
            $session->setExpirationSeconds($config->system->session_timeout);
        }
        Zend_Registry::set('session', $session);
    }
}
