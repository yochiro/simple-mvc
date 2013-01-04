<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Implements a simple resource identifier
 *
 * It identifies resources from their filenames based on extension.
 * Extensions not listed in this class will yield a default mimetype
 *
 * @package smvc
 */
final class Utils_ResourceIdentifier
{
    /**
     * Singleton instance
     */
    private static $_instance = null;


    /** @var array $types assoc array of supported extensions to mime types */
    private $types= array();

    /** @var string $defext the default extension of the system */
    private $defext = '';

    /** @var string $defmime the default mime type of the system */
    private $defmime = 'application/octet-stream';


    private static function _instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    private function __construct()
    {
        $this->addDefaultTypes();
    }


    /**
     * Associates a mime type to an extension
     *
     * @param string $ext the extension (including period)
     * @param string $mime the mime type
     */
    public function addType($ext, $mime)
    {
        $this->types[$ext] = $mime;
    }

    /**
     * Removes a type from the system
     *
     * @param string $ext the extension of the type (including period)
     */
    public function delType($ext)
    {
        unset($this->types[$ext]);
    }

    /**
     * Sets the default extension
     *
     * The initial default extension is an empty string.
     *
     * @param string $ext the extension (including period)
     */
    public function setDefaultExtension($ext)
    {
        $this->defext = $ext;
    }

    /**
     * Sets the default mime type
     *
     * The initial default mime type is application/octet-stream.
     *
     * @param string $mime the mime type
     */
    public function setDefaultMimeType($mime)
    {
        $this->defmime = $mime;
    }

    /**
     * Adds some default types
     *
     * The types are added in order of expected occurance, which improves
     * performance since associative arrays in PHP are ordered like a queue.
     */
    public function addDefaultTypes()
    {
        $this->addType('.css', 'text/css');
        $this->addType('.png', 'image/png');
        $this->addType('.jpg', 'image/jpeg');
        $this->addType('.js',  'text/javascript');
        $this->addType('.ico', 'image/vnd.microsoft.icon');
        $this->addType('.pdf', 'application/pdf');
        $this->addType('.htm', 'text/html');
        $this->addType('.html', 'text/html');
        $this->addType('.csv', 'text/csv');
        $this->addType('.doc', 'application/msword');
        $this->addType('.xls', 'application/vnd.ms-excel');
        $this->addType('.zip', 'application/zip');
        $this->addType('.txt', 'text/plain');
        $this->addType('.gif', 'image/gif');
    }

    /**
     * Identifies the resource type given a filename
     *
     * If no extension match is found, the defaults are returned.
     *
     * @param string $filename the filename
     * @return array of 'found' (bool), 'ext', and 'mime'
     */
    public static function identify($filename)
    {
        $ri = self::_instance();
        $ret = array('found' => false,
                     'ext' => $ri->defext,
                     'mime' => $ri->defmime);
        foreach ($ri->types as $ext => $mime) {
            if (Utils::str_endsWith($filename, $ext, true)) {
                $ret['found'] = true;
                $ret['ext'] = $ext;
                $ret['mime'] = $mime;
                break;
            }
        }
        return $ret;
    }

}
