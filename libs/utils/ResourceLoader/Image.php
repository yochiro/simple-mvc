<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * This class is a loader for image type resources.
 *
 * @package smvc
 */
final class Utils_ResourceLoader_Image extends Utils_ResourceLoader_Abstract
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
     * Loads and output specified image
     *
     * Image can be preprocessed through resource plugins.
     *
     * Config :
     * [namespace]
     *   system:
     *     plugins:
     *       resources:
     *         image:
     *           - plugin1
     *           - plugin2
     *           ...
     *
     * @param string $resource the resource to load
     * @param string $mime the resource mimetype
     */
    protected function doLoad($resource, $mime)
    {
        header('Cache-control: max-age=290304000, public');
        header('Pragma: public');
        $config = Zend_Registry::get('config');
        if (Utils::str_endsWith($resource, 'ico')) {
            header('Content-type:'. $mime);
            readfile($resource);
            exit();
        }
        try {
            $img = WideImage::load($resource);
        } catch(WideImage_UnsupportedFormatException $wie) {
            header('Content-type:'. $mime);
            readfile($resource);
            return;
        }

        if (isset($config->system->plugins->resources->image)) {
            $plugCls = 'Plugin_Resource_%s';
            foreach ($config->system->plugins->resources->image->toArray() as $plugin) {
                $cls = sprintf($plugCls, ucfirst($plugin));
                if (@class_exists($cls) && in_array('Iface_Plugin_Resource', class_implements($cls))) {
                    $p = new $cls();
                    $img = $p->process($resource, $mime, $img);
                }
            }
        }
        $type = substr($mime, strpos($mime, '/')+1);
        $img->output($type);
    }
}
