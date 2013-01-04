<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Static helpers which return paths for the various framework parts.
 *
 * Folders paths returned :
 * - baseDir : site root dir
 * - libDir : the framework root dir
 * - configDir : the config folder under site root dir
 * - resourcesDir : the resources folder under site root dir (can be a symlink to an external folder)
 * - uploadDir : the upload folder under resourcesDir (can be a symlink to an external folder)
 * - imagesDir : the images folder under resourcesDir (can be a symlink to an external folder)
 * - logDir : the logs folder under resourcesDir (can be a symlink to an external folder)
 * - cacheDir : the cache folder under resourcesDir (can be a symlink to an external folder)
 * - langDir : the i18n gettext folder under site root dir | namespaced
 * - skinDir : the site skin data under site root dir | namespaced
 *
 * Files paths returned :
 * - configFile : path to site configuration file under configDir | When not found : N/A
 * - uploadFile : path to an uploaded resource under uploadDir | When not found : return false
 * - logFile : path to a log file under logDir | When not found : N/A
 * - cacheFile : path to a cached file under cacheDir | When not found : input or null
 * - langFile : path to a gettext resource under langDir | When not found : die
 * - skinFile : path to a skin data under skinDir | When not found : return false
 * - scriptFile : path to a script file under skinDir | When not found : return false
 *
 * MVC related paths :
 * - layoutDirs : list of paths to savant3 template layouts | namespaced
 * - modelDirs : list of paths to models | namespaced
 * - viewDirs : list of paths to view files | namespaced
 * - controllerDirs : list of paths to controller folders | namespaced
 *
 * @package smvc
 */
final class Utils_ResourceLocator

{
    const CONFIG_DIR = 'config';
    const CONFIG_FILENAME = 'config.yaml';
    const LAYOUT_DIR = 'layouts';
    const VIEW_DIR = 'views';
    const CONTROLLER_DIR = 'controllers';
    const MODEL_DIR = 'models';

    private static $_defaultConfig = null;
    private static $_namespaces = array();


    public static function setResourcesConfig($default, array $namespaces)
    {
        assert(!is_null($default));
        self::$_defaultConfig = $default;
        self::$_namespaces = $namespaces;
    }


    // ------ Directory ------ //

    public static function baseDir()
    {
        return rtrim(DATA_DIR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    public static function libDir()
    {
        return rtrim(LIB_DIR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    public static function configDir()
    {
        return self::baseDir().self::CONFIG_DIR.DIRECTORY_SEPARATOR;
    }

    public static function resourcesDir()
    {
        $res = self::baseDir().self::$_defaultConfig['resources'].DIRECTORY_SEPARATOR;
        if (is_link(rtrim($res, DIRECTORY_SEPARATOR))) {
            $res = realpath($res).DIRECTORY_SEPARATOR;
        }
        return $res;
    }

    public static function uploadDir()
    {
        $upd = self::resourcesDir().self::$_defaultConfig['upload'].DIRECTORY_SEPARATOR;
        if (is_link(rtrim($upd, DIRECTORY_SEPARATOR))) {
            $upd = realpath($upd).DIRECTORY_SEPARATOR;
        }
        return $upd;
    }

    public static function imagesDir()
    {
        $img = self::resourcesDir().self::$_defaultConfig['images'].DIRECTORY_SEPARATOR;
        if (is_link(rtrim($img, DIRECTORY_SEPARATOR))) {
            $img = realpath($img).DIRECTORY_SEPARATOR;
        }
        return $img;
    }

    public static function logDir()
    {
        $log = self::resourcesDir().self::$_defaultConfig['log'].DIRECTORY_SEPARATOR;
        if (is_link(rtrim($log, DIRECTORY_SEPARATOR))) {
            $log = realpath($log).DIRECTORY_SEPARATOR;
        }
        return $log;
    }

    public static function cacheDir()
    {
        $cache = self::resourcesDir().self::$_defaultConfig['cache'].DIRECTORY_SEPARATOR;
        if (is_link(rtrim($cache, DIRECTORY_SEPARATOR))) {
            $cache = realpath($cache).DIRECTORY_SEPARATOR;
        }
        return $cache;
    }

    public static function langDir()
    {
        return self::baseDir().self::$_defaultConfig['lang'].DIRECTORY_SEPARATOR;
    }

    public static function skinDir()
    {
        return self::baseDir().self::$_defaultConfig['skin'].DIRECTORY_SEPARATOR;
    }


    // ------ Files ------ //

    public static function configFile()
    {
        return self::configDir().self::CONFIG_FILENAME;
    }

    public static function uploadFile($resource)
    {
        $upd = self::uploadDir();
        $f = realpath($upd.$resource);
        if (is_file($f) && 0 === strpos($f, $upd)) {
            return $f;
        }
        return false;
    }

    public static function logFile()
    {
        $log = self::logDir();
        // reset returns the first element of the array... no array peek function in PHP
        $ns = reset(self::$_namespaces);
        return $log.$ns.'.txt';
    }

    public static function cacheFile($file, $retNullWhenNotFound=false)
    {
        $cache = self::cacheDir();
        $f = realpath($cache).DIRECTORY_SEPARATOR.$file;
        if (!(is_file($f) && 0 === strpos($f, $cache)) &&
            $retNullWhenNotFound === true) {
            $f = null;
        }
        return $f;
    }

    public static function langFile($loc)
    {
        $langPath = self::langDir().'%s'.DIRECTORY_SEPARATOR.$loc->toString().'.mo';
        foreach (self::$_namespaces as $ns) {
            $f = realpath(sprintf($langPath, $ns));
            if (is_file($f) && 0 === strpos($f, self::langDir())) {
                return $f;
            }
        }
        throw new Exception('Cannot find language resource : ' . $loc->toString());
    }

    public static function skinFile($resource)
    {
        $skinPath = self::skinDir().'%s'.DIRECTORY_SEPARATOR.$resource;
        // Include default dir for skin file
        foreach (array_merge(self::$_namespaces, array('default')) as $ns) {
            $f = realpath(sprintf($skinPath, $ns));
            if (is_file($f) && 0 === strpos($f, self::skinDir())) {
                return $f;
            }
        }
        return false;
    }

    public static function scriptFile($resource)
    {
        return self::skinFile(DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.ltrim($resource,DIRECTORY_SEPARATOR));
    }


    // --------- MVC related directories ---------- //

    public static function layoutDirs()
    {
        $dirs = array();
        $layoutPath = self::baseDir().self::LAYOUT_DIR.DIRECTORY_SEPARATOR.'%s'.DIRECTORY_SEPARATOR;
        foreach (self::$_namespaces as $ns) {
            $f = realpath(sprintf($layoutPath, $ns));
            if (false !== $f) {
                $dirs[$ns] = $f.DIRECTORY_SEPARATOR;
            }
        }
        array_push($dirs, self::libDir().self::LAYOUT_DIR.DIRECTORY_SEPARATOR);
        return $dirs;
    }

    public static function modelDirs()
    {
        $dirs = array();
        $modelPath = self::baseDir().self::MODEL_DIR.DIRECTORY_SEPARATOR.'%s'.DIRECTORY_SEPARATOR;
        foreach (self::$_namespaces as $ns) {
            $f = realpath(sprintf($modelPath, $ns));
            if (false !== $f) {
                $dirs[$ns] = $f.DIRECTORY_SEPARATOR;
            }
        }
        return $dirs;
    }

    public static function viewDirs()
    {
        $dirs = array();
        $viewPath = self::baseDir().self::VIEW_DIR.DIRECTORY_SEPARATOR.'%s'.DIRECTORY_SEPARATOR;
        foreach (self::$_namespaces as $ns) {
            $f = realpath(sprintf($viewPath, $ns));
            if (false !== $f) {
                $dirs[$ns] = $f.DIRECTORY_SEPARATOR;
            }
        }
        array_push($dirs, self::libDir().self::VIEW_DIR.DIRECTORY_SEPARATOR);
        return $dirs;
    }

    public static function controllerDirs()
    {
        $dirs = array();
        $ctrlPath = self::baseDir().self::CONTROLLER_DIR.DIRECTORY_SEPARATOR.'%s'.DIRECTORY_SEPARATOR;
        foreach (self::$_namespaces as $ns) {
            $f = realpath(sprintf($ctrlPath, $ns));
            if (false !== $f) {
                $dirs[$ns] = $f.DIRECTORY_SEPARATOR;
            }
        }
        array_push($dirs, self::libDir().self::CONTROLLER_DIR.DIRECTORY_SEPARATOR);
        return $dirs;
    }
}
