<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * This class is the default resource loader, if no loader is assigned to
 * a specific mime type.
 *
 * @package smvc
 */
final class Utils_ResourceLoader_Default extends Utils_ResourceLoader_Abstract
{
    private static $_instance = null;


    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Sets content-type to specified mime type and outputs content as is
     *
     * @param string $resource the resource to load
     * @param string $mime the resource mimetype
     */
    protected function doLoad($resource, $mime)
    {
        header('Cache-control: max-age=290304000, public');
        header('Pragma: public');
        header('Content-type: ' . $mime);
        readfile($resource);
    }
}
