<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 *
 * Interface for a factory that manages MVC instances.
 */

 /*
 * This interface has three methods ;
 * model($modelname)
 * view($viewname)
 * controller($controllername)
 *
 * Parameter to the methods is a logical name that maps to an actual class name implementing
 * resp. Iface_Model, Iface_Controller and Iface_View.
 * Name to actual classname mapping depends on the implementing class.
 *
 * @package smvc
 */
interface Iface_MVC
{
    /**
     * Returns a new model instance designated by its name
     *
     * @param string $modelname the model name to instantiate
     * @return Iface_Model the model if found, or null
     */
    public function model($modelname);

    /**
     * Returns a new view instance designated by its name
     *
     * @param string $viewname the view name to instantiate
     * @return Iface_View the view if found, or null
     */
    public function view($viewname);

    /**
     * Returns a new controller instance designated by its name
     *
     * @param string $controllername the controller name to instantiate
     * @return Iface_Controller the controller if found, or null
     */
    public function controller($controllername);
}
