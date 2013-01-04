<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Interface that defines a resource pre-render plugin
 *
 * Resource render plugins are ran by the ResourceLoader before the resource is output.
 * The resource file, its mimetype are passed.
 * As resources can be of various types (image, binary...), additional parameters may
 * be passed as well.
 * It allows to perform various operations before the resource is output.
 *
 * @package smvc
 */
interface Iface_Plugin_Resource
{
    /**
     * Processes the resource render plugin
     *
     * @param string $resource the resource file
     * @param string $mime the mime type of the resource
     */
    function process($resource, $mime);
}
