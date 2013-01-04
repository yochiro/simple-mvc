<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Interface that defines a view render plugin
 *
 * View render plugins are ran by the view renderer just before the rendering process.
 * The view object is passed as a parameter.
 * It allows to perform various operations before the render process starts.
 *
 * @package smvc
 */
interface Iface_Plugin_View
{
    /**
     * Processes the view render plugin
     *
     * @param Iface_View $view the view object about to be rendered
     */
    function process(Iface_View $view);
}
