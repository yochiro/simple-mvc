<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * This class manages CSS and javascript to be loaded when a page is rendered
 *
 * addScript adds a javascript file, or javascript code to its list.
 * A priority allows to prioritize javascript files/code.
 *
 * addStylesheet adds a stylesheet to its list for specified media.
 *
 * ResourceLoader only stores these js/css, but is not in charge of
 * outputting them to the page.
 * Stored values can be retrieved using getScripts and getStylesheets.
 *
 * @package smvc
 */
final class Utils_ResourceLoader
{
    /**
     * List of javascript to load
     * @var array
     */
    private static $_js  = array();

    /**
     * List of CSS to load
     * @var array
     */
    private static $_css = array();


    /**
     * Adds specified javascript file/code to the list of javascript to load
     *
     * If the first parameter if a file, then $embedded should be false.
     * If first parameter is some javascript code, then $embedded should be true.
     * The optional $prio parameter assigns a priority (default 50) for the js
     * about to be added.
     *
     * @param string $fileOrScript javascript file, or javascript code
     * @param boolean true if $fileOrScript is code, false if file
     * @param integer $prio the javascript load priority (Default=50)
     */
    public static function addScript($fileOrScript, $embedded=false, $prio=50)
    {
        if (!$embedded) {
            self::$_js[$prio][$fileOrScript] = $fileOrScript;
        } else {
            self::$_js[$prio][] = $fileOrScript;
        }
    }

    /**
     * Adds specified CSS to the list of CSS to load, applied to specified media
     *
     * $media can be a CSV of valid CSS media, eg. handheld, screen, print...
     *
     * @param string $filename the CSS to add to the load list
     * @param string $media the CSV of media the CSS applies to (default=screen)
     */
    public static function addStylesheet($filename, $media='screen')
    {
        self::$_css[$filename] = $media;
    }

    /**
     * Returns all javascript currently set for loading
     *
     * @return array[prio][filename|idx => filename|code] of javascript to load
     */
    public static function getScripts()
    {
        return self::$_js;
    }

    /**
     * Returns all CSS currently set for loading
     *
     * @return array[filename => media] of CSS to load
     */
    public static function getStylesheets()
    {
        return self::$_css;
    }

    /**
     * Outputs specified resource of specified mime type
     *
     * @param string $resource the resource
     * @param string resource mime type
     */
    public static function outputResource($resource, $mime)
    {
        $loaderCls = get_class().'_%s';
        $loader = sprintf($loaderCls, ucfirst(substr($mime, 0, strpos($mime, '/'))));
        if (@class_exists($loader) && is_subclass_of( $loader, 'Utils_ResourceLoader_Abstract')) {
            $loader = call_user_func(array($loader, 'instance'));
        }
        else {
            $loader = call_user_func(array(sprintf($loaderCls, ucfirst('default')), 'instance'));
        }
        $loader->load($resource, $mime);
    }
}
