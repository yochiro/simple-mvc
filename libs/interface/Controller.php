<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines an interface for a simple MVC controller
 *
 * dispatch is the main entry point for a given controller :
 * it should retrieve the requested page, process it and dispatch it
 * to the correct location. The internal dispatching process is left
 * to the implementation class.
 * redirect performs an external redirect (new HTTP request) to specified
 * url.
 * isForward should tell the dispatcher whether an internal redirect
 * (same HTTP request) should be performed.
 * Utility methods getPost/isPost, getRequestMethod, getParams/isGet handle
 * POST/GET data.
 * setView/view handles the view(s) associated to this controller
 *
 * @package smvc
 */
interface Iface_Controller
{
    /**
     * Dispatches request and performs associated action
     *
     * Implementation decides on how to dispatch (whole-grained VS fine-grained)
     * and how to process it.
     * The dispatch must :
     * - set flag so that isForward returns true should the FrontDispatcher loops
     *   and redispatches the request it forwarded to.
     * - return itself for chaining if no external redirect.
     * - redirect to requested page if external redirect is required.
     * Once dispatch is done, view() should return :
     *    - The view to be rendered if no loop must occur (!isForward)
     *    - The internal forwarded request if it must loop (isForward)
     *
     * @return $this for chaining
     * @throw RequestException if request is invalid
     */
    function dispatch();

    /**
     * Performs an external redirect to specified url
     *
     * An external redirect means that a new HTTP request will be issued.
     * This method thus does not return. (current request ends)
     *
     * @param string $to the url to be redirected to
     */
    function redirect($to);

    /**
     * Returns whether current request has an internal redirect
     *
     * Internal redirects do not issue a new HTTP request, but
     * a new dispatch process should be performed with the
]    * forwarded request as the new request.
     *
     * @return true|false whether there is an internal redirect
     */
    function isForward();

    /**
     * Returns specified HTTP header value
     *
     * @param string $header header name to return
     * @return string the specified header value
     */
    function getHeader($header);

    /**
     * Returns the request method ('POST', 'GET', 'PUT', 'DELETE')
     *
     * @return string the request method
     */
    function getRequestMethod();

    /**
     * Returns the POST data array
     *
     * Returned data may be sanitized during the process, or may be returned
     * as is, up to the implementor.
     *
     * @return array POST values (sanitized or not)
     */
    function getPost();

    /**
     * Returns the GET data array
     *
     * Returned data may be sanitized during the process, or may be returned
     * as is, up to the implementor.
     *
     * @return array GET values (sanitized or not)
     */
    function getParams();

    /**
     * Returns whether current request is a POST request
     *
     * @return true|false is current request a POST request ?
     */
    function isPost();

    /**
     * Returns whether current request is a GET request
     *
     * @return true|false is current request a GET request ?
     */
    function isGet();

    /**
     * Sets the view to render once the controller completed its process
     *
     * @param string $view the view to set
     * @return $this for chaining
     */
    function setView($view);

    /**
     * Returns the current view to be rendered
     *
     * @return string the view to be rendered
     */
    function view();
}
