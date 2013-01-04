<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * View utilities
 *
 * filter is a static method which (lazily) returns the view root object and apply to all its children
 * when called the specified filter function and optionally sorted according to the order function.
 * order is a static method only sorting based on specified function, without filtering.
 *
 * View_Filter is a proxy class used to simulate a closure environment for filter/order functions
 * when lazily evaluating the view children (they are retrieved when function is called).
 * It overrides children and parent method so that returned view(s) are themselves proxied again
 * by that same class, passing stored filter/order functions to them everytime.
 *
 * @package smvc
 */
final class Utils_Views
{
    /**
     * Returns whether two view objects belong to the same DIRECTORY.
     *
     * This is different from hierarchy level : that is, index.phtml and all
     * its sibling files in a given directory, when fed to this function, would
     * return true, while index.phtml is actually one level higher to the others.
     *
     * @param Iface_View $v1 the first view to compare
     * @param Iface_View $v2 the second view to compare
     * @return boolean true if both views are in the same dir, false otherwise
     */
    public static function inSameDir(Iface_View $v1, Iface_View $v2)
    {
        return ($v1->dirname() === $v2->dirname());
    }

    /**
     * Returns a view object whose children are sorted based on order fn $o and with views matching filter fn $f.
     *
     * If $o is not supplied, view will not be ordered and children() will return them
     * in their original position, i.e. sorted by the underlying filename.
     * $f and $o are callback functions. In PHP 5.3, they can be Closures;
     * Otherwise, they can be a function created through create_function.
     * Object returned is not an actual Iface_View instance but a decorator class
     * View_Filter, that will wrap all methods as is, except for parent() and children()
     * (which both return Iface_View and array of Iface_View) so that the two callbacks
     * methods initially supplied to the filter method are propagated to the children
     * objects. This is necessary because both methods are lazy-evaluating the view object
     * to return.
     * To allow the View_Filter to be handled as if it were a real view, it implements
     * Iface_View as well.
     * From the array of children will be removed views not matching conditions defined
     * by the filter callback.
     * By default, the root view is "/" (top of hierarchy). Optionally, a new top
     * can be supplied : all children under the specified root will then be considered instead.
     *
     * @param callback $f filter callback function
     * @param callback $o optional sort callback function
     * @param string $from optional starting view to use as root
     * @return Iface_View the View, with filtered and sorted children/parent
     */
    public static function filter($f, $o=null, $from='/')
    {
        $view = MVC::view($from);
        return new View_Filter($view, $o, $f);
    }

    /**
     * Returns a view object whose children are sorted based on order fn $o
     *
     * Object returned is not an actual Iface_View instance but a decorator class
     * View_Filter, that will wrap all methods as is, except for parent() and children()
     * (which both return Iface_View and array of Iface_View) so that the callback
     * method initially supplied to the order method are propagated to the children
     * objects. This is necessary because both methods are lazy-evaluating the view object
     * to return.
     * To allow the View_Filter to be handled as if it were a real view, it implements
     * Iface_View as well.
     * children() will return the same values as the underlying Iface_View children method,
     * but reordered based on the order callback function.
     *
     * @param callback $o sort callback function
     * @return Iface_View the View, with sorted children/parent
     */
    public static function order($o)
    {
        return self::filter(null, $o);
    }
}


final class View_Filter implements Iface_View
{
    private $_fn = null;
    private $_or = null;
    private $_view = null;

    public function __construct(SimpleView $v, $or = null , $fn = null)
    {
        $this->_view = $v;
        $this->_or = $or;
        $this->_fn = $fn;
    }

    public function content()
    {
        return $this->_view->content();
    }

    public function parent()
    {
        return ($this->_view->isRoot()?null:(new self($this->_view->parent(), $this->_or, $this->_fn)));
    }

    public function children()
    {
        $c = $this->_view->children($this->_or, $this->_fn);
        $v = array();
        foreach ($c as $_c) {
            $v[] = new self($_c, $this->_or, $this->_fn);
        }
        return $v;
    }

    public function __get($f)
    {
        return $this->_view->$f;
    }

    public function __isset($f)
    {
        return isset($this->_view->$f);
    }

    public function __set($f, $v)
    {
        return $this->_view->$f = $v;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_view, $method), $args);
    }
}
