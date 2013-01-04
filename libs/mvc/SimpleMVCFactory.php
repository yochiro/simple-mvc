<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 *
 * Factory to manage the SimpleMVC classes
 */

/**
 * Factory for the "SimpleMVC" implementation of an MVC framework.
 *
 * model returns a SimpleModel instance
 * view returns a SimpleView instance
 * controller returns a SimpleController instance
 *
 * Location to base folders in the file system for each of the MVC components are defined
 * in @see Utils_ResourceLocator :
 * - modelDirs
 * - viewDirs
 * - controllerDirs
 *
 * The "name => classname to instantiate" rule is defined in this class.
 *
 * Each SimpleXXX class is purposely kept simple, ie. no base abstract class is defined,
 * nor has any complex class hierarchy.
 * However, it is possible to create a whole new different set of MVC classes (as long as they
 * implements Iface_XXX interfaces) and still be useable by this framework;
 * MVC is an abstract factory and expects an Iface_MVC interface like this class to function;
 * As long as the only explicit references to SimpleXXX classes are in this factory, the framework
 * itself will be loosely coupled to this set of MVC classes.
 * The application though will need to subclass SimpleController explicitely to work.
 *
 * Usually called through MVC static class when the latter defines it as its abstract factory to use.
 *
 * @package SimpleMVC
 */
final class SimpleMVCFactory implements Iface_MVC
{
    /**
     * Caches previous instances of SimpleViews
     * @var array
     */
    private static $_views = array();


    /**
     * @see Iface_MVC::model($modelname)
     */
    public function model($modelname)
    {
        return $this->doModel($modelname);
    }

    /**
     * @see Iface_MVC::view($viewname)
     */
    public function view($viewname)
    {
        return $this->doView($viewname);
    }

    /**
     * @see Iface_MVC::controller($controllername)
     */
    public function controller($controllername)
    {
        return $this->doController($controllername);
    }


    /**
     * @see MVC::model($modelname)
     * @see Iface_MVC::model($modelname)
     * @see ResourceLocator::modelDirs()
     *
     * Models are namespaced: the first match among the available namespaces is returned.
     *
     * Expected Model class name is Model_ucfirst($modelname).
     * For each model path returned by modelDirs :
     * - If model class is not defined in the PHP environment yet,
     *   Include the file <model path>/<ctrl_model>.php if it exists
     * - If previous include succeeded, or if model class name was already defined,
     *   then try to instantiate it (if subclass of Iface_Model).
     * - else try next candidate in the path list
     *
     * @param string $modelname the canonical name of the model to return
     * @return SimpleModel if model found
     */
    private function doModel($modelname)
    {
        $cls = null;
        $paths = Utils_ResourceLocator::modelDirs();
        $fileName = ucfirst($modelname);
        $clsName = 'Model_'.$fileName;
        while (is_null($cls) && !empty($paths)) {
            $p = array_shift($paths);
            $f = $p.$fileName.'.php';
            if (!@class_exists($clsName, false) && is_file($f)) {
                include($f);
            }
            if (@class_exists($clsName) && is_subclass_of($clsName, 'Iface_Model')) {
                $cls = new $clsName();
            }
        }
        return $cls;
    }

    /**
     * @see MVC::view($viewname)
     * @see Iface_MVC::view($viewname)
     * @see ResourceLocator::viewDirs()
     *
     * Views are namespaced: the first match among the available namespaces is returned.
     *
     * Two special folders are added for the SimpleMVC implementation :
     * - site root dir view base folder/static
     * - framework view base folder/static
     * `static' subfolder includes all views that should be not namespaced, eg.
     * 404 not found views, error pages... (though those may be namespaced too).
     *
     * This factory caches previously created view objects :
     * If a view named $viewname is found in the cached views, then return that one immediately.
     * => This factory applies the flyweight design pattern on view objects.
     *
     * If not cached yet :
     * For each view path returned by viewDirs :
     * - Check if a file named index.phtml is located inside <view path>/viewname/ folder.
     *   If it exists, then that file becomes the view.
     * - Check if a file named $viewname.phtml is located inside <view path>/ folder.
     *   If it exists, then that file becomes the view.
     * - else try next candidate in the path list
     *
     * A PageNotFoundException is thrown if a view $viewname was not found in any of the defined paths.
     *
     * @param string $viewname the canonical name of the view to return
     * @return SimpleView if view found
     * @throw PageNotFoundException if view is not found
     */
    private function doView($viewname)
    {
        if (array_key_exists($viewname, self::$_views)) {
            return self::$_views[$viewname];
        }

        $paths = Utils_ResourceLocator::viewDirs();
        // Push static views path in the path list : check before namespaced ones
        array_unshift($paths, Utils_ResourceLocator::baseDir().
                              Utils_ResourceLocator::VIEW_DIR.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR);
        array_unshift($paths, Utils_ResourceLocator::libDir().
                              Utils_ResourceLocator::VIEW_DIR.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR);
        $file = null;
        $ns = null;

        while (is_null($file) && !empty($paths)) {
            $ns = key($paths);
            $p = array_shift($paths);
            $f = trim($viewname, DIRECTORY_SEPARATOR);
            if (is_dir($p) && is_file($p.$f.DIRECTORY_SEPARATOR.'index.phtml')) {
                $file = $p.$f.DIRECTORY_SEPARATOR.'index.phtml';
            }
            elseif (is_file($p.$f.'.phtml')) {
                $file = $p.$f.'.phtml';
            }
        }
        if (is_null($file)) {
            throw new PageNotFoundException('Cannot find requested view : ' . $viewname);
        }
        $view = SimpleView::create($file);
        $view->__namespace = $ns;
        $view->__name = rtrim($viewname, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        self::$_views[$viewname] = $view;
        return $view;
    }

    /**
     * @see MVC::controller($controllername)
     * @see Iface_MVC::controller($controllername)
     * @see ResourceLocator::controllerDirs()
     *
     * Controllers are namespaced: the first match among the available namespaces is returned.
     *
     * Expected Controller class name is ucfirst($controllername)Controller.
     * For each controller path returned by controllerDirs :
     * - If controller class is not defined in the PHP environment yet,
     *   Include the file <controller path>/controllername/<ctrl_classname>.php if it exists
     * - If previous include succeeded, or if controller class name was already defined,
     *   then try to instantiate it (if subclass of Iface_Controller).
     * - else try next candidate in the path list
     *
     * @param string $controllername the canonical name of the controller to return
     * @return SimpleController if controller found, null otherwise
     */
    private function doController($controllername)
    {
        $cls = null;
        $paths = Utils_ResourceLocator::controllerDirs();
        $clsName = ucfirst($controllername).'Controller';
        while (is_null($cls) && !empty($paths)) {
            $p = array_shift($paths);
            $f = $p.$controllername.DIRECTORY_SEPARATOR.$clsName.'.php';
            if (!@class_exists($clsName, false) && is_file($f)) {
                include($f);
            }
            if (@class_exists($clsName) && is_subclass_of($clsName, 'Iface_Controller')) {
                $cls = new $clsName(null);
            }
        }
        return $cls;
    }
}
