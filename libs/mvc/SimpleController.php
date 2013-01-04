<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines a base class for a simple (as in basic, not stupid) controller
 *
 * This simple controller uses the following approach to dispatch requests :
 * - main dispatch method further dispatches the request based on the <strong>request method</strong>
 * - Only POST and GET methods are supported at the moment
 * - Each controller subclassing this SimpleController must overridde either or both of
 *   post|get dispatch method. Additional dispatching is the responsibility of the subclass.
 *   Default implementation of SimpleController's get|post dispatcher is to raise a RequestException.
 *   (Request Method not supported).
 * - Subclass can setForward to specified controller if needed as an internal redirect (default), or
 *   external (@see FrontDispatcher::dispatchRequest).
 * - Subclass get|post dispatch can throw either RequestException (will forward to request error page),
 *   or any other exception (will forward to application error page).
 * - forwardError can be called by dispatch method to explicitely request an error page to be shown.
 *
 * Most methods defined by Iface_Controller are delegated to Utils_Request
 *
 * @package SimpleMVC
 */
abstract class SimpleController implements Iface_Controller
{
    /**
     * Did current request ended in error (need to forward to error page)
     * @var boolean
     */
    private $_isError = false;

    /**
     * Does current request forwards to another ?
     * @var boolean
     */
    private $_forward = false;

    /**
     * If forward, is it an external redirect ?
     * @var boolan
     */
    private $_isRedirect = false;

    /**
     * HTTP redirect value if redirect requested
     * @var int
     */
    private $_httpRedirect = Utils_Request::REDIRECT_FOUND;

    /**
     * Should we redirect on POST requests ? (default false)
     * @var bool
     */
    private $_redirectOnPost = false;

    /**
     * Some user data
     * @var array
     */
    private $_context = array();

    /**
     * View name to render upon completion
     * (Default is same as controller name)
     * @var string
     */
    private $_view = null;


    /**
     * Ctor
     *
     * The view name can be changed during the dispatch process
     * using setView, and can be retrieved using view().
     *
     * @param string $view the view name to render upon completion
     */
    public function __construct($view)
    {
        $this->setView($view);
    }

    /**
     * Sets the view name to render upon completion
     *
     * The view name does not refer to an actual filename nor is an Iface_View instance.
     * It should be a name that resolves to a view filename (further leading to an object instance),
     * when passed as a parameter of Utils_Views::get(...).
     *
     * @param string $view the view name to render
     * @return $this for chaining
     */
    final public function setView($view)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * Returns the view name set for this controller
     *
     * @return string the view name to render
     */
    final public function view()
    {
        return $this->_view;
    }

    /**
     * TODO
     */
    final public function getHostLocalReferer()
    {
        return trim(Utils_Url::getHostLocalReferer(), '/');
    }

    /**
     * Entry point for requesy dispatching
     *
     * Process is delegated to protected doDispatch method.
     * Postprocess is done by checking the forward status;
     * If an external redirect was requested during dispatch, then
     * perform the redirect IF the error flag is not set (ie. dispatch did not call forwardError).
     * Otherwise return itself.
     *
     * @return $this for chaining
     */
    final public function dispatch()
    {
        if ($this->redirectOnPost()) {
            $this->doRedirectOnPostGET();
        }
        $ret = $this->doDispatch();
        if ($this->redirectOnPost()) {
            $this->doRedirectOnPostPOST();
        }

        if ($this->isForward() && !$this->isError() && $this->_isRedirect) {
            $this->redirect($this->view());
        }
        return $this;
    }

    /**
     * Performs an external redirect to specified URL
     *
     * @param string $to url to redirect to
     */
    final public function redirect($to)
    {
        $to = Utils_Url::buildUri($to);
        Utils_Request::sendHttpStatusCode($this->_httpRedirect);

        header('Location:'.$to, true, $this->_httpRedirect);
        exit();
    }

    /**
     * Returns whether current request needs to be forwarded or not
     *
     * The flag is true for both internal and external redirects.
     * isRedirect differentiates between both forward types.
     *
     * @return boolean should request be forwarded ?
     */
    final public function isForward()
    {
        return $this->_forward;
    }

    /**
     * Sets forward flag and request forward to specified URL, optionally external
     *
     * @param string $to url to forward to
     * @param boolean should it be an external redirect ?
     * @param int HTTP Redirect value, Redirect Found 302) if not specified
     * @return $this for chaining
     */
    final public function setForward($to, $isRedirect=false, $http_redirect=Utils_Request::REDIRECT_FOUND)
    {
        $this->_forward = true;
        $this->_view = $to;
        $this->_isRedirect = $isRedirect;
        $this->_httpRedirect = $http_redirect;
        return $this;
    }

    /**
     * Has request raised an error during dispatch ?
     *
     * @return boolean error?
     */
    final public function isError()
    {
        return $this->_isError;
    }

    /**
     * TODO
     */
    final public function getRequestURI()
    {
        return Utils_Request::getRequestURI();
    }

    /**
     * Returns the POST array
     *
     * TODO: POST data are sanitized by Utils_Request, thus this method
     * should be preferred over using $_POST directly in the controller.
     *
     * @return array POST data
     */
    final public function getPost()
    {
        return Utils_Request::getPost();
    }

    /**
     * Returns specified request parameter's value from optional source
     *
     * If $from is null or omitted, the parameter $name will be looked up
     * in $_GET, then $_POST in this order. null is returned if none was found.
     * $from can be either 'get' or 'post'
     *
     * @param string $name the parameter name to look at
     * @param string $from null|get|post where to look at
     * @return string the parameter value, or null if not found
     */
    final public function getParam($name, $from=null)
    {
        return Utils_Request::getParam($name, $from=null);
    }

    /**
     * Returns the GET array
     *
     * TODO: GET data are sanitized by Utils_Request, thus this method
     * should be preferred over using $_GET directly in the controller.
     *
     * @return array GET data
     */
    final public function getParams()
    {
        return Utils_Request::getParams();
    }

    /**
     * Returns the request method
     *
     * Can be 'GET'|'POST'|'PUT'|'DELETE'
     *
     * @return string the request method
     */
    final public function getRequestMethod()
    {
        return Utils_Request::getRequestMethod();
    }

    /**
     * Returns whether current request is a POST request
     *
     * @return boolean true if POST request, false otherwise
     */
    final public function isPost()
    {
        return Utils_Request::isPost();
    }

    /**
     * Returns whether current request is a GET request
     *
     * @return boolean true if GET request, false otherwise
     */
    final public function isGet()
    {
        return Utils_Request::isGet();
    }

    /**
     * Returns whether current request is secure (HTTPS)
     *
     * @return boolean true if https, false otherwise
     */
    final public function isSecure()
    {
        return Utils_Request::isSecure();
    }

    /**
     * Returns specified header value
     *
     * @see Utils_Request for more details on how it is retrieved.
     * @param string $header the header name to look at
     * @return strign the header value if found, null otherwise
     */
    final public function getHeader($header)
    {
        return Utils_Request::getHeader($header);
    }


    /**
     * sets the error flag and requests a forward to the error page
     *
     * The error page can show the error message stored in the registry
     * as 'error'. Additionally, 'stack_trace' has the exception object
     * if applicable. Useful in debug mode.
     *
     * @param string $msg the error message to store in the registry
     * @param $httpStatusCode the HTTP status code to send back to the client
     * @param Exception $e optional exception to store in the registry
     * @return $this for chaining
     * @post The error message is registered in the Zend_Registry as 'error'
     * @post If specified, exception is registered in the Zend_Registry as 'stack_trace'
     * @post $this->isError() returns true
     */
    final public function forwardError($msg, $httpStatusCode, $e = null)
    {
        $this->addRequestData('error', $msg);
        $this->addRequestData('stack_trace', $e);
        $this->setForward('error');
        $this->_isError = true;
        return $this;
    }


    final public function __get($key)
    {
        return $this->getRequestData($key);
    }

    final public function __isset($key)
    {
        return array_key_exists($key, $this->_context);
    }

    final public function __set($key, $val)
    {
        return $this->addRequestData($key, $val);
    }

    /**
     * Returns request data stored by the controller
     *
     * @param string $key if not null, return data stored with specified key
     * @return array the request data array
     */
    final public function getRequestData($key=null)
    {
        return $this->doGetRequestData($key);
    }

    /**
     * Adds or replaces a request data with specified key=>value pair
     *
     * @param string $key the key to store the value into
     * @param mixed $value the request data to store
     * @return $this for chaining
     *
     */
    final public function addRequestData($key, $value)
    {
        return $this->doAddRequestData($key, $value);
    }

    /**
     * Returns whether current controller should redirect on a POST request
     *
     * @return bool true if redirect on POST
     */
    final public function redirectOnPost()
    {
        return $this->_redirectOnPost;
    }

    /**
     * Sets the redirect on POST flag
     *
     * @param bool the new redirect on POST value
     * @return $this for chaining
     */
    final public function setRedirectOnPost($rop)
    {
        $this->_redirectOnPost = $rop;
        return $this;
    }

    /**
     * Main dispatch process
     *
     * - If POST request, delegates to doPostDispatch (protected)
     * - If GET request, delegates to doGetDispatch (protected)
     * - If any other request method, forward to error page
     * - If RequestException is raised in doGet|PostDispatch, propagate it
     * - If any other exception is caught, forward to error page
     *
     * @return boolean true if no error, false otherise
     */
    protected function doDispatch()
    {
        $config = Zend_Registry::get('config');
        $charset = (isset($config->system->charset)?$config->system->charset:'UTF-8');
        // Defaults content type to be text/html utf-8 => Can be overridden within each controller
        FrontDispatcher::instance()->addHeader('Content-type', 'text/html; charset='.$config->system->charset);
        try {
            if ($this->isPost()) {
                $this->doPostDispatch();
            }
            elseif ($this->isGet()) {
                $this->doGetDispatch();
            }
            else {
                throw new RequestException('Invalid method');
            }
        } catch (RequestException $re) {
            throw $re;
        } catch (Exception $e) {
            $this->forwardError($e->getMessage(),
                                Utils_Request::SERVER_INTERNAL_SERVER_ERROR, $e);
            return false;
        }
        return true;
    }

    /**
     * Default implementation for GET dispatcher : throw RequestException (invalid request)
     */
    protected function doGetDispatch() { throw new RequestException('Invalid GET request'); }
    /**
     * Default implementation for POST dispatcher : throw RequestException (invalid request)
     */
    protected function doPostDispatch() { throw new RequestException('Invalid POST request'); }

    /**
     * If Redirect on POST enabled, retrieves POST data in the GET request
     */
    final protected function doRedirectOnPostGET()
    {
        $n = Zend_Registry::get('namespace');
        $sessns = $n.'_request';

        Zend_Session::start(); // Session may not be started : start it here
        // P/R/G pattern : GET stores back session into request data
        if ($this->isGet() && Zend_Session::namespaceIsset($sessns)) {
            $s = new Zend_Session_Namespace($sessns, true);
            foreach ($s as $k => $v) {
                $this->$k = $v;
            }
            Zend_Session::namespaceUnset($sessns);
        }
    }

    /**
     * If Redirect on POST enabled, setup hook for GET redirect
     */
    final protected function doRedirectOnPostPOST()
    {
        $n = Zend_Registry::get('namespace');
        $sessns = $n.'_request';

        Zend_Session::start(); // Session may not be started : start it here
        // P/R/G pattern : POST stores request data into session
        if ($this->isPost()) {
            Zend_Session::namespaceUnset($sessns);
            $s = new Zend_Session_Namespace($sessns, true);
            foreach ($this->getRequestData() as $k => $v) {
                $s->$k = $v;
            }
            $this->setForward($this->view(), true, Utils_Request::REDIRECT_SEE_OTHER);
        }
    }

    /**
     * Returns the data stored during the controller request process
     *
     * @param string $key if not null, return data stored with specified key
     * @return array the request data
     */
    private function doGetRequestData($key)
    {
        $data = null;
        if (!is_null($key) && isset($this->_context[$key])) {
            $data = $this->_context[$key];
        }
        elseif (is_null($key)) {
            $data = $this->_context;
        }
        return $data;
    }

    /**
     * Adds or replaces a request data with specified key=>value pair
     *
     * It stores the value into the context.
     *
     * @param string $key the key to store the value into
     * @param mixed $value the request data to store
     * @return $this for chaining
     *
     */
    private function doAddRequestData($key, $value)
    {
        $this->_context[$key] = $value;
        return $this;
    }
}


