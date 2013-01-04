<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines the entry point for dispatching client requests
 *
 * FrontDispatcher is a singleton that dispatches requests to
 * the proper controller that can handle it.
 * dispatchRequest is the entry point for this class.
 * addHeader allows to add HTTP headers to send prior to
 * the view rendering.
 * It internally stores the view to render after the request
 * has been dispatched and can perform the rendering process
 * on it through render().
 * render takes an optional $viewname string parameter which allows to
 * render specified view instead.
 *
 * @package SimpleMVC
 */
final class FrontDispatcher implements Iface_Singleton
{
    /**
     * @var FrontDispatcher the singleton instance
     */
    private static $_singleton = null;

    /**
     * HTTP headers to send alongwith the rendered view
     * @var array[string] array of HTML headers
     */
    private $_headers = array();

    /**
     * View to render after dispatching
     * @var Iface_View
     */
    private $_view = null;

    /**
     * Application configuration
     * @var Utils_Config
     */
    private $_config = null;

    /**
     * Request data after request dispatch to controller(s)
     * @var array
     */
    private $_reqData = array();


    /**
     * @see Iface_Singleton::instance()
     */
    public static function instance()
    {
        if (is_null(self::$_singleton)) {
            self::$_singleton = new self();
        }
        return self::$_singleton;
    }

    /**
     * Dispatches incoming request to proper controller(s) and stores related view
     *
     * Several controllers can be chain-called during the process, in a determined order.
     *
     * Each controller should be in a subdirectory named with the controller canonical name.
     * The subdirectory should have a class definition named CnameController (fist letter uppercase).
     * e.g. controller with cname "foo" shall be defined in foo/FooController.php.
     * The location of the subdir foo can be under any of the base controller dirs returned by
     * Utils_ResourceLocator::controllerDirs(), referred below as <ctrldirs>, meaning any of
     * the directories in the list. The first one found is used.
     *
     * Controllers, when found, are looked up then processed in the following order :
     * - Default Controller `default' : <ctrldirs>/default/DefaultController
     * - Filtered controller name : the name is generated from the request, stripped of all non
     * alphanumeric characters. E.g. if request is `foo/bar/baz', controller name will be foobarbaz.
     * i.e. <ctrldirs>/foobarbaz/FoobarbazController.
     * - Filtered controller name with request parameters :
     * Allows to use SEO friendly URLs, with a base (filtered) controller and its subsequent query parameters.
     * e.g. foo/bar/baz/value/value2 : looks up foo/FooController, and treats the subsequent tokens
     * (bar, baz, value, value2) as a list of query parameters.
     * Any combination of those controllers is valid, as long as the conditions are met.
     * Example :
     * Under <ctrldirs> (any), the following subfolders for controllers are defined :
     * - default
     *   -> DefaultController
     * - foosearch
     *   -> FoosearchController
     * - foo
     *   -> FooController
     * If request is foo/search, then :
     * - DefaultController is processed
     * - FoosearchController is processed, as all tokens glued together match the controller name
     * - FooController is processed, with `search' as an item of the list of request parameters.
     * If request is foo/search/keyword, then :
     * - DefaultController is processed
     * - FooController is processed, with [search,keyword] as items of the list of req. params.
     * Note that in this case, FoosearchController is not called, as the matching name glued from
     * the request tokens is now Foosearchkeyword.
     *
     * Upon completion of each controller , dispatchRequest takes control back,
     * and internally loops by calling itself as long as controller initiated
     * an internal forward ($controller->isForward() returns true).
     * This can potentially lead to an infinite loop, so the implementor
     * of such controllers should be careful to check that forwards do not loop indefinitely!
     * looping is performed by calling this function again, only with $loop parameter set to true.
     * Request data that were stored in the controller are merged with the $reqData of the FrontController
     * and so on for each iteration. Eventually, $reqData contains the request data of all processed controllers.
     * as many times as necessary if multiple internal forwards are performed.
     * When inside an internal loop, the "default" controller is not processed again.
     * If not internal forward is necessary, controller returns the view name to render.
     * (default view name defaults to unfiltered controller name - aka original request -).
     * e.g. : request foo/bar => controller : foobar/FoobarController , view = foo/bar
     *
     * A view object is then looked up using the view name and is stored internally
     * for delayed rendering (responsibility of the caller, @see Init::parseRequest).
     *
     * If configuration is set, View plugins defined in the configuration are processed
     * on the view before rendering. @see self::render()
     *
     * [Controller instances]
     * MVC::controller(controllername) returns the controller instance associated with
     * given controller name (filtered request).
     * The controller base path is dynamically determined from the request :
     * request == 'foo' => 'COMMONLIB|APPLIB/controllers/<namespace>/foo' (@see Utils_ResourceLocator::controllerDirs()).
     * request == 'foo/bar' => 'COMMONLIB|APPLIB/controllers/<namespace>/foobar' (@see Utils_ResourceLocator::controllerDirs()).
     * @see MVC::controller($controllername) and SimpleMVCFactory::controller($controllername).
     *
     * Note: depending on the controller process, it may not return at all :
     * eg. when issuing an external redirect, it is performed by the controller itself.
     * When issuing an ajax request, the Ajax response is sent by the controller then ends
     * current request as no view needs to be rendered.
     *
     * @param string $request incoming request to dispatch
     * @param bool $loop is it an internal dispatch loop ?
     * @param array $reqData the request data from previous loop if any
     * @return $this for chaining
     */
    public function dispatchRequest($request, $loop=false)
    {
        $dispatchedView = $request;
        $filtered = preg_replace('/[^a-zA-Z0-9]*/', '', $request);

        $parts = explode('/', trim($request, '/'));

        $ctrls = array();
        if (!$loop) {
            array_push($ctrls, array(MVC::controller('default'), $parts));
        }
        $baseFiltered = preg_replace('/[^a-zA-Z0-9]*/', '', array_shift($parts));
        array_push($ctrls, MVC::controller($filtered));
        array_push($ctrls, array(MVC::controller($baseFiltered), $parts));
        foreach ($ctrls as $ctrl) {
            $reqP = array();
            if (is_array($ctrl)) {
                list($ctrl, $reqP) = $ctrl;
            }
            if (!is_null($ctrl)) {
                $ctrl->requestParams = $reqP;
                $ctrl->baseRequest   = $baseFiltered;
                $ctrl->setView($dispatchedView);
                $dispatchLoop = false;
                $dispatchedView = $ctrl->dispatch()->view();
                $dispatchLoop = $ctrl->isForward();
                $this->_reqData = array_merge($this->_reqData, $ctrl->getRequestData());
                if ($dispatchLoop) {
                    return $this->dispatchRequest($dispatchedView, true);
                }
            }
        }

        $this->_view = MVC::view($dispatchedView);
        return $this;
    }

    /**
     * Adds an HTML header to the list of headers to be sent when rendering view
     *
     * @param string $header valid HTML header
     * @param mixed $value the header value
     * @return $this for chaining
     */
    public function addHeader($header, $value)
    {
        $this->_headers[$header] = $value;
        return $this;
    }

    /**
     * Sets specified configuration
     *
     * @param Utils_Config $config configuration to set
     * @return $this for chaining
     */
    public function setConfig(Utils_Config $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Renders view previously stored for rendergin during request dispatching
     *
     * Normal views are application namespaced, ie. each namespace can hold the same
     * view names, overridding being handled through the same order as each override
     * configured for each namespace.
     *
     * @return NOTHING (buffered output)
     */
    public function render($viewname = null)
    {
        $this->_sendHeaders();
        $view = $this->_view;
        if (!is_null($viewname)) {
            $view = MVC::view($viewname);
        }

        /* Set request data as view meta */
        foreach ($this->_reqData as $k => $v) {
            $view->addMeta($k, $v);
        }

        $this->viewPlugins($view);
        $view->render();
    }


    private function _sendHeaders()
    {
        if (!empty($this->_headers)) {
            foreach ($this->_headers as $t => $v) {
                header($t.': '.$v);
            }
        }
    }

    /**
     * Runs view render plugins defined in the configuration file
     *
     * View render plugins must implement the interface Iface_Plugin_View.
     * Applications may add their own view render plugins as needed.
     * The only parameter passed to each plugin is the view to be rendered.
     *
     * Configuration file:
     *   system->plugins->view : list of view render plugins to process
     *     format: string, translated into Plugin_View classname : foo => Plugin_View_Foo
     *
     * @param Iface_View $view the view to be rendered
     */
    private function viewPlugins(Iface_View $view)
    {
        $plugins = isset($this->_config->system->plugins->view)?
                   $this->_config->system->plugins->view->toArray():array();

        $plugCls = 'Plugin_View_%s';
        foreach ($plugins as $plugin) {
            $cls = sprintf($plugCls, ucfirst($plugin));
            if (@class_exists($cls) &&
                in_array('Iface_Plugin_View', class_implements($cls))) {
                $p = new $cls();
                $p->process($view);
            }
        }
    }
}
