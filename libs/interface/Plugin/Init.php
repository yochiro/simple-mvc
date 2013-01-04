<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Interface that defines an init plugin
 *
 * Init plugins are ran when the current request is parsed, before
 * it is dispatched to the front controller.
 * The config object is passed to allow access to the configuration settings,
 * as well as the application namespace and the current request URI.
 *
 * @package smvc
 */
interface Iface_Plugin_Init
{
    /**
     * Processes the init plugin
     *
     * @param Utils_Config $config the configuration settings
     * @param string $namespace the application namespace
     * @param string $request the requested page
     */
    function process(Utils_Config $config, $namespace, $request);
}
