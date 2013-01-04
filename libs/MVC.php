<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */


/**
 * Abstract factory to manage MVC instances
 *
 * This class contains three methods :
 * - model($modelname)
 * - view($viewname)
 * - controller($controllername)
 *
 * Each returns a new instance of specified MVC class.
 * Parameter is the logical name that should map to an M,V or C classname.
 * Mapping is performed by the concrete factory implementation wrapped by this class.
 *
 * @package smvc
 */
final class MVC
{
    /**
     * MVC factory managing MVC classes
     * @var Iface_MVC
     */
    private static $_factory = null;


    /**
     * Returns a new model instance matching specified canonical model name
     *
     * The model name supplied mapping depends on the underlying factory used.
     *
     * @param string $modelname the canonical model name to instantiate
     * @return Iface_Model new model instance if found, null otherwise
     */
    public static function model($modelname)
    {
        return self::_instance()->model($modelname);
    }

    /**
     * Returns a new view instance matching specified canonical view name
     *
     * The view name supplied mapping depends on the underlying factory used.
     *
     * @param string $viewname the canonical view name to instantiate
     * @return Iface_View new view instance
     * @throw PageNotFoundException if view not found
     */
    public static function view($viewname)
    {
        return self::_instance()->view($viewname);
    }

    /**
     * Returns a new controller instance matching specified canonical controller name
     *
     * The controller name supplied mapping depends on the underlying factory used.
     *
     * @param string $controllername the canonical controller name to instantiate
     * @return Iface_Controller new controller instance if found, null otherwise
     */
    public static function controller($controllername)
    {
        return self::_instance()->controller($controllername);
    }


    /**
     * Instantiates the factory used by this abstract factory
     *
     * Currently, SimpleMVC is the only MVC implementation available
     *
     * @return Iface_MVC factory which to delegate MVC instantiation process to
     */
    private static function _instance()
    {
        if (is_null(self::$_factory)) {
            // Simple MVC factory used
            self::$_factory = new SimpleMVCFactory();
            assert(self::$_factory instanceof Iface_MVC);
        }
        return self::$_factory;
    }
}
