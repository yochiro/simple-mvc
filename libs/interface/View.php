<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines an interface for a simple MVC view
 *
 * The view defined is very simple and only contains
 * the basic methods required to render content.
 * content() returns the view content, while
 * magic functions __get, __isset and __set
 * can be used to assign dynamic content to
 * the view being rendered. the dynamic content can then
 * be stored in the implementation and processed when rendering the content.
 */
interface Iface_View
{
    /**
     * Returns the view content
     *
     * if metadata is set to the view object,
     * it may be processed when rendering the content before
     * it is returned by this method.
     *
     * @return string the view rendered content
     */
    function content();

    /**
     * Gets specified metadata from the view
     *
     * @param string $k the metadata name to return
     * @return mixed the metadata name if found, null otherwise
     */
    function __get($k);

    /**
     * Sets specified metadata to the view using specified value
     *
     * @param string $k the metadata name to set
     * @param mixed $v the metadata value
     */
    function __set($k, $v);

    /**
     * Returns whether specified metadata is set for current view
     *
     * @param string $k the metadata to look for
     * @return true|false whether metadata is set for current view
     */
    function __isset($k);
}
