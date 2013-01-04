<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 *
 * Application Bootstrap : all HTTP requests go through this initialization process
 *
 * Required constants :
 * - DATA_DIR : absolute path to application root folder
 *              Must include a trailing PATH_SEPARATOR.
 * - LIB_DIR : absolute path to the framework root folder. This Init class should be under LIB_DIR/libs/
 *             Must include a trailing PATH_SEPARATOR.
 * - OPT_DIR : absolute path to the opt folder. opt/ contains all 3rd party libs/apps used by the framework
 *             Must include a trailing PATH_SEPARATOR.
 * - BOOTSTRAP_FILE : Must contain the filename of the PHP boostrap calling this Init. Usually basename(__FILE__)
 * - NAMESPACE : [Optional] Defines the application namespace. Must have an entry in the config file
 *               If not specified, SERVER_NAME is used.
 *
 * Dependencies (in opt folder) :
 * - Zend (part) : Zend framework 1.9
 *   # Cache, Db, Json, Loader, Locale, Log, Registry, [Session] (if init plugin Session used), Translate
 * - spyc : YAML parser (config file, view file headers) (MIT licence)
 * - Savant3 : Simple Templating engine (LGPL licence)
 *
 * Auto loader uses Zend class naming : Foo_Bar_MyClass will map to location Foo/Bar/MyClass.php
 * Any class respecting the Zend naming convention will be auto loaded if found under any of the include path.
 * default include path is extended with the following :
 * - OPT_DIR
 * - OPT_DIR/spyc : Spycs class names do not follow Zend convention
 * - OPT_DIR/savant3 : first letter is lowercase and thus does not match the autoloader
 * - LIB_DIR/libs : this framework core classes and utilities
 * - DATA_DIR/libs : the application libs, for any additional utility classes it may need
 * - LIB_DIR/libs/mvc : The core MVC classes used by the application (View, Controller, Model, Layout)
 * - DATA_DIR/opt : The opt 3rd party not included in the framework that the application might provide
 * - DATA_DIR/libs/mvc : Application custom or additional classes related to MVC
 * - DATA_DIR/controllers : Folder where all the applications controllers are stored
 * - DATA_DIR/views : Folder where all application views are stored
 *
 * In addition, the following assumptions regarding class names are made for the given location :
 * - LIB_DIR/libs/utils : Foo.php => Utils_Foo
 * - LIB_DIR/libs/interface : Foo.php => Iface_Foo
 * - LIB_DIR/libs/plugins : Foo.php => XYZPlugin_Foo (XYZ == Init, ViewRender)
 * - DATA_DIR/models : Foo.php => Model_Foo
 * - DATA_DIR/libs/interface : Foo.php => Iface_Foo
 * - DATA_DIR/libs/plugins : Foo.php => XYZPlugin_Foo (XYZ == Init, ViewRender)
 */

ini_set('html_errors', true);
define('OPT_DIR', LIB_DIR . 'opt/');
set_include_path(rtrim(get_include_path(), PATH_SEPARATOR)
                 . PATH_SEPARATOR . OPT_DIR
                 . PATH_SEPARATOR . OPT_DIR . 'spyc'
                 . PATH_SEPARATOR . OPT_DIR . 'savant3'
                 . PATH_SEPARATOR . LIB_DIR . 'libs'
                 . PATH_SEPARATOR . DATA_DIR . 'libs'
                 . PATH_SEPARATOR . DATA_DIR . 'opt'
                 . PATH_SEPARATOR . LIB_DIR . 'libs/mvc'
                 . PATH_SEPARATOR . DATA_DIR . 'libs/mvc');

// ------------------------------------
// ZEND AUTO LOADER SETUP
// ------------------------------------
require_once('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$libDirLoader = new Zend_Loader_Autoloader_Resource(
                array('basePath'  => LIB_DIR,
                      'namespace' => ''));
$dataDirLoader = new Zend_Loader_Autoloader_Resource(
                 array('basePath'  => DATA_DIR,
                       'namespace' => ''));
$libDirLoader->addResourceType( 'utils',  'libs/utils/', 'Utils');
$libDirLoader->addResourceType( 'iface',  'libs/interface/', 'Iface');
$libDirLoader->addResourceType( 'plugins',   'libs/plugins/', 'Plugin');
$dataDirLoader->addResourceType('models', 'models/', 'Model');
$dataDirLoader->addResourceType('iface',  'libs/interface/', 'Iface');
$dataDirLoader->addResourceType('plugins',   'libs/plugins/', 'Plugin');
$autoloader->pushAutoloader($libDirLoader);
$autoloader->pushAutoloader($dataDirLoader);
$autoloader->setFallbackAutoloader(true);

/**
 * Web application bootstrap.
 *
 * Usage example :
 * - Create docroot folder the web server points to for the web application.
 * - In the webapp docroot, create a php script that does the following :
 *   1. Define required constants (DATA_DIR, LIB_DIR, OPT_DIR)
 *   2. <code>include(LIB_DIR.'libs/Init.php')</code> (This file)
 *   3. Call <code>Init::main()</code>
 * - If possible, it is recommended that docroot only contains the boostrap script, while the application
 *   and the framework resides somewhere else. LIB_DIR should point to that root folder.
 *   e.g.:
 *   - docroot/
 *       - myapp.php
 *   - smvc/
 *       - libs ...
 *   - myapp/
 *       - controllers
 *       - views ...
 * - Use .htaccess mod_rewrite to redirect ALL requests to the new boostrap file.
 *   This class also handles resources (css, js, images...).
 * LIB_DIR/docroot/ contains a sample of such bootstrap, named namespace.php,
 * as well as a sample .htaccess.
 *
 * -------------------------------------------------------------------
 * For the setup of myapp, refer to README and INSTALL
 * -------------------------------------------------------------------
 *
 * @package smvc
 */
final class Init
{
    /** @var array $_req the request parts */
    private $_req = null;

    /** @var Utils_Config $_config the config resource */
    private $_config = null;

    /** @var bool $_debug the enable/disable status of debug */
    private $_debug = null;

    /** @var Zend_Log $_log the log */
    private $_log = null;

    /** @var Zend_Locale $_locale the locale */
    protected $_locale = null;

    /** @var Zend_Translate $_xl8 the Zend_Translate instance */
    protected $_xl8 = null;


    /**
     * Called before run(). Allows to perform various pre-run actions
     *
     * Expects a class named Plugin_PreRun to be defined within the scope
     * of the current application.
     * If such a class is found, it must implement Iface_PreRun.
     * Run its process() method if conditions match.
     */
    protected function preRun()
    {
        if (@class_exists('Plugin_PreRun') &&
            in_array('Iface_PreRun', class_implements('Plugin_PreRun'))) {
            $preRun = new Plugin_PreRun();
            $preRun->process();
        }
    }

    /**
     * Starts the script timer
     *
     * @post The timer is registered in the Zend_Registry as 'timer'
     * @throws WrappedException on timer initialization error
     */
    protected function startTimer()
    {
        try {
            $timer = new Utils_Timer();
            $timer->start('page');
            Zend_Registry::set('timer', $timer);
        } catch (Exception $e) {
            throw new WrappedException('unable to start timer', $e);
        }
    }

    /**
     * Loads the configuration file
     *
     * Config entries are explained in LIB_DIR/config/README
     *
     * The config is registered in the Zend_Registry as 'config'.
     *
     * @param string $namespace The namespace to load in the config.
     * @post The Utils_Config object is registered in the Zend_Registry as 'config'
     * @post The alt namespace (override) -if defined- is registered in the Zend_Registry as 'alt_namespace'
     * @throws WrappedException on config load error
     */
    protected function loadConfig($namespace='default')
    {
        try {
            $this->_config = new Utils_Config(Utils_ResourceLocator::configFile(),
                                              str_replace('-', '_', $namespace));
            Zend_Registry::set('config', $this->_config);
            $alt_ns = (isset($this->_config->overrides))?$this->_config->overrides:null;
            Zend_Registry::set('alt_namespace', $alt_ns);
        } catch (Exception $e) {
            throw new WrappedException('unable to load config', $e);
        }
    }

    /**
     * Sets the debug setting from the config
     *
     * Call first:
     *   loadConfig()
     *
     * Configuration file:
     *   system->debug
     *     valid: true | false
     *     default: false
     *     required: no
     *
     * @post The debug flag is registered in the Zend_Registry as 'debug'
     */
    protected function setDebug()
    {
        $this->_debug = (null != $this->_config->system->debug)
                     && (true == $this->_config->system->debug);
        if ($this->_debug) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', true);
            ini_set('display_startup_errors', true);
            ini_set('html_errors', true);
        }
        Zend_Registry::set('debug', $this->_debug);
    }

    /**
     * Initialize the resources lookup paths from the config
     *
     * Lookup paths can be chained if application overrides other settings.
     * Some paths are shared among all overridden instances, while some are namespaced using the application namespace.
     * Namespaced resources are :
     * - skin
     * - lang
     * Shared resources are :
     * - images
     * - upload
     * - scripts
     * - logs
     *
     * Call first:
     *   loadConfig()
     *
     * Configuration file:
     *   directories->resources : Base directory for images, upload, script
     *   directories->images    : Contains static images. Under resources. Can be symlink to external dir
     *   directories->upload    : Contains user uploaded files/images (eg. from CMS). Under resources. Can be symlink
     *   directories->script    : Contains Javascript files. Under resources.
     *   directories->skin      : Contains all skin resources (graphics, js, css...). Under site root dir (DATA_DIR)
     *   directories->lang      : Contains .po and compiled .mo translation files. Under site root dir (DATA_DIR)
     *   directories->log       : Contains logs files. Under site root dir (DATA_DIR). chmod 2775
     *     valid: path
     *     default: none (sane values supplied in LIB_DIR/config-default/config.yaml
     *     required: yes
     */
    protected function initResources()
    {
        $chain = $this->_config->configChain();
        // Remove default config
        $default = array_pop($chain);
        $default = $default['directories'];
        $namespaces = array_keys($chain);
        Utils_ResourceLocator::setResourcesConfig($default, $namespaces);
    }

    /**
     * Sets the system default timezone to UTC and sets session timezone
     *
     * Call first:
     *   loadConfig()
     *
     * Configuration file:
     *   system->timezone
     *     format: Timezone ISO string (eg. UTC, Asia/Tokyo...)
     *     default: UTC
     *     required: no
     *
     * @post The configured timezone is registered in the Zend_Registry as 'timezone'
     */
    protected function initTimezone()
    {
        $default_timezone = 'UTC';
        date_default_timezone_set($default_timezone);
        $timezone = $this->_config->system->timezone;
        if (is_null($timezone)) {
            $timezone = $default_timezone;
        }
        Zend_Registry::set('timezone', $timezone);
    }

    /**
     * Inits request string and call init plugins
     *
     * Call first:
     *   loadConfig()
     *
     * Before the bootstrap that calls Init::main() is ran, Apache's mod_rewrite
     * rules should redirect all HTTP requests to the bootstrap file, and append
     * the original request string to it.
     * eg.
     * http://my-application/request/this/path/
     * where my-application docroot points to something like /var/www/foo/docroot/
     * would be rewritten as eg.
     * /var/www/foo/docroot/myapp-docroot/my-app.php/request/this/path/
     * my-app.php would itself call Init::main() after setting LIB_DIR, DATA_DIR...
     * correctly.
     * SERVER built-in variable PHP_SELF contains the request string as supplied by
     * Apache mod_rewrite above.
     *
     * This method takes the bootstrap filename as a parameter, as well as the application namespace.
     * original request is extracted from PHP_SELF using the specified filename as a matching pattern.
     * ie. ...server-host.../namespace.php/something/ => (...server-host...)/<namespace.php>(/something) => /something
     * ERROR is sent back if the above pattern could not be found in PHP_SELF.
     *
     * Once request is extracted, init plugins are called.
     *
     * @param string $filename the bootstrap script filename (php file)
     * @param string $namespace the application namespace
     */
    private function initRequest($filename, $namespace='default')
    {
        $filename = str_replace('.', '\.', $filename);
        preg_match('/(.*)\/' . $filename . '(\/.*)$/',
                   $_SERVER['PHP_SELF'], $req);
        if (!isset($req[1]) || !isset($req[2])) {
            die('initRequest :: ERROR');
        }
        $this->_req = array('base' => $req[1], 'url' => $req[2]);
        $this->initPlugins($namespace, $this->_req['url']);
    }

    /**
     * Initializes the logging system
     *
     * Log files are stored under the log folder defined in the configuration file.
     * This directory is not namespaced, but each application has its own log file named `app_namespace.log'.
     *
     * Call first:
     *   loadConfig()
     *
     * Configuration file:
     *   system->loglevel
     *     format: EMERG|0 | ALERT|1 | CRIT|2 | ERR|3 | WARN|4 | NOTICE|5 | INFO|6 | DEBUG|7
     *     default: WARN
     *     required: no
     *
     * @param string $namespace the namespace for the logging to use
     * @post The log instance is registered in the Zend_Registry as 'log'
     * @throws Exception on log file initialization error
     */
    protected function initLogging($namespace='default')
    {
        $loglevel_default = 4;
        $loglevel = $this->_config->system->loglevel;
        if (null == $loglevel) {
            $loglevel = $loglevel_default;
        } else {
            $idx = array_search($loglevel,
                array('EMERG', 'ALERT', 'CRIT', 'ERR', 'WARN',
                      'NOTICE', 'INFO', 'DEBUG'));
            if (false === $idx) {
                $loglevel = strpos('01234567', $loglevel);
                if (false === $loglevel) {
                    $loglevel = $loglevel_default;
                }
            } else {
                $loglevel = $idx;
            }
        }
        try {
            $this->_log = new Zend_Log();
            $this->_log->addWriter(new Zend_Log_Writer_Stream(Utils_ResourceLocator::logFile()));
            $this->_log->addFilter(new Zend_Log_Filter_Priority($loglevel));
            Zend_Registry::set('log', $this->_log);
        } catch (Exception $e) {
            throw new WrappedException('unable to initialize logging', $e);
        }
    }

    /**
     * Initializes the locale
     *
     * locale instance is the current locale to be used in the application.
     * locales_support is the list of locales available for the application.
     *
     * Call first:
     *   loadConfig()
     *
     * Configuration file:
     *   system->locales
     *     format: CSV (list of locales in la_LO format)
     *     default: 'en_US'
     *     required: no
     *
     * @param string $request the desired locale. Overrides config if set.
     * @post The locale instance is registered in the Zend_Registry as 'locale'
     * @post The locales_support array is registered in the Zend_Registry as 'locales_support'
     */
    protected function initLocale($request=null)
    {
        $locales = (null == $this->_config->system->locales)
                 ? array('en_US')
                 : explode(',', $this->_config->system->locales);
        $locsel = Utils_LocaleSelector::instance();
        $locsel->setSupported($locales);
        if (null !== $request) {
            $locsel->setRequested($request);
        }
        $this->_locale = new Zend_Locale($locsel->getLocaleString());
        Zend_Registry::set('locale', $this->_locale);
        $locales_support = array();
        foreach ($locales as $loc) {
            $loclang = substr($loc, 0, 2);
            $locales_support[$loc] = array(
                $this->_locale->getTranslation($loclang, 'language',
                                               $this->_locale->getLanguage()),
                $this->_locale->getTranslation($loclang, 'language',
                                               $loclang));
        }
        Zend_Registry::set('locales_support', $locales_support);
    }

    /**
     * Initializes the Zend_Translate instance
     *
     * Call first:
     *   loadConfig()
     *   initLocale()
     *
     * Translation file format : GETTEXT
     *
     * It uses current locale to determine which gettext file to load.
     * Language file must be a valid compiled .mo file named la_LO.mo.
     * It must be located under the lang directory defined in the config.
     * lang folder is namespaced, so language file must be located under
     * current namespace subfolder (eg. langs/myapp_ns/en_US.mo).
     * If current application config overrides others, then all overridden applications
     * namespaces are also looked up until a lang file for current locale is found.
     *
     * @post The translate instance is registered in the Zend_Registry as 'Zend_Translate'
     * @throws WrappedException on translate initialization error
     */
    protected function initTranslate()
    {
        try {
            $this->_xl8 = new Zend_Translate(Zend_Translate::AN_GETTEXT,
                Utils_ResourceLocator::langFile($this->_locale),
                $this->_locale->getLanguage(),
                array('disableNotices' => true));
            Zend_Registry::set('Zend_Translate', $this->_xl8);
        } catch (Exception $e) {
            throw new WrappedException('unable to initialize translate', $e);
        }
    }

    /**
     * Runs initialization plugins defined in the configuration file
     *
     * Init plugins must implement the interface Iface_Plugin_Init.
     * This framework supplies two init plugins that applications can setup :
     * - MongoDB : connects to a mongoDB database
     * - Mysql : connects to a Mysql database
     * Both adds their own values to Zend_Registry.
     * Applications may add their own init plugins as needed.
     * Init plugins may defiine specific values required in the config file;
     * these should be documented in the init plugin class.
     * Init plugins are passed three parameters :
     * - $config : the configuration object loaded by loadConfig
     * - $namespace : the current application namespace
     * - $request : the requested page
     *
     * Called by :
     *   initRequest
     *
     * Configuration file:
     *   system->plugins->init : list of init plugins to process
     *     format: string, translated into Plugin_Init classname : foo => Plugin_Init_Foo
     *
     * @param string $namespace application namespace
     * @param string $request current request
     */
    private function initPlugins($namespace, $request)
    {
        $plugins = isset($this->_config->system->plugins->init)?
                   $this->_config->system->plugins->init->toArray():array();

        $plugCls = 'Plugin_Init_%s';
        foreach ($plugins as $plugin) {
            $cls = sprintf($plugCls, ucfirst($plugin));
            if (@class_exists($cls) &&
                in_array('Iface_Plugin_Init', class_implements($cls))) {
                $p = new $cls();
                $p->process($this->_config, $namespace, $request);
            }
        }
    }

    /**
     * Parses and dispatches the request string
     *
     * The core process of an HTTP request is handled by this method.
     *
     * Call initRequest first.
     *
     * Once request is extracted, the following steps are performed :
     * - check if request is a static resource and serves it if that is the case => [END]
     * - if not static resource, dispatch request to the FrontController
     *   - PageNotFoundException raised : render "404 page not found" view
     *   - RequestException raised : render "request error" view
     *   - Exception raised : rethrow and wrap it in a WrappedException => renders init error page
     * - No exception : call rendering process on FrontDispatcher  => [END]
     *
     * @param string $namespace the application namespace
     */
    private function parseRequest($namespace='default')
    {
        $this->handleResources($this->_req['url']);

        try {
            FrontDispatcher::instance()->setConfig($this->_config)
                                       ->dispatchRequest($this->_req['url'])
                                       ->render();
        } catch(PageNotFoundException $pnfe) {
            $this->spawn404Error();
        } catch(RequestException $re) {
            $this->spawnRequestError($re);
        } catch(Exception $e) {
            throw new WrappedException('Error during request process', $e);
        }
    }

    /**
     * Handles static resources that do not pass through the MVC process
     *
     * Called by :
     *   parseRequest, before request is dispatched to FrontController
     *
     * Currently, things that are considered resources are :
     * - scripts : URL starts with /script/
     * - skin resources : URL starts with /skin/
     * - uploaded resources : URL starts /upload/
     *
     * If URL starts with either, the specified resource is looked up on the server.
     * Note that this not a direct mapping to a physical location : Utils_ResourceLocator
     * is responsible for finding the requested resource physically.
     * If URL marks the request as a resource but actual file was not found on the server,
     * an error HTTP status code is sent back to the client.
     * If requested resource was found but is a php script, return a failure code to the client as well.
     * Otherwise, identify its type and returns its content after setting the cache-control header.
     * All resources are told to be cached.
     *
     * Init ends at the exit of this method if request was a resource, else this method returns
     * and parseRequest continues.
     *
     * @param string $resource the requested resource
     */
    private function handleResources($resource)
    {
        $retResource = null;
        if (0 === strpos($resource, '/script/')) {
            $retResource = Utils_ResourceLocator::scriptFile(substr($resource,8));
        }
        elseif (0 === strpos($resource, '/skin/')) {
            $retResource = Utils_ResourceLocator::skinFile(substr($resource, 5));
        }
        elseif (0 === strpos($resource, '/upload/')) {
            $retResource = Utils_ResourceLocator::uploadFile(substr($resource,7));
        }

        // Is a resource but file was not found
        if (false === $retResource) {
            Utils_Request::sendHttpStatusCode(Utils_Request::CLIENT_NOT_FOUND);
            exit(1);
        }
        // If resource file not found, any call above will die. If null, then none of the above
        // If $resource is null, then requested  is not a resource: do nothing.
        elseif (!is_null($retResource)) {
            if (!Utils::str_endsWith($retResource, 'php', true)) {
                $mime = Utils_ResourceIdentifier::identify($retResource);
                $mime = $mime['mime'];
                Utils_ResourceLoader::outputResource($retResource, $mime);
            }
            else {
                Utils_Request::sendHttpStatusCode(Utils_Request::CLIENT_UNSUPPORTED_MEDIA_TYPE);
            }
            exit();
        }
    }

    /**
     * Ends execution of current request due to an non recoverable initialization error
     *
     * As this error happened early in the process, there is no view/page to show to the user,
     * and thus text-only message is sent back to the client.
     *
     * @param WrappedException $we the exception that describes the issue
     */
    private function spawnInitError(WrappedException $we)
    {
        Utils_Request::sendHttpStatusCode(Utils_Request::SERVER_INTERNAL_SERVER_ERROR);
        die(sprintf('Init error! : %s (reason : %s)', $we->getMessage(),
                    $we->getException()->getMessage()));
    }

    /**
     * Renders error page when a request error occurs
     *
     * View canonical name to render : `rerror'
     *
     * The application is responsible for providing such a page
     * in the available view paths (@see Utils_ResourceLocator::viewDirs()).
     * Zend_Registry gets two more values, 'error' and 'stack_trace',
     * respectively set to the error message describing the issue,
     * and the exception object.
     * The RequestException should contain the HTTP status code to send back
     * to the client.
     * If the rerror view is not found, the script dies.
     *
     * @param RequestException $re the exception that describes the issue
     */
    private function spawnRequestError(RequestException $re)
    {
        Zend_Registry::set('error', $re->getMessage());
        Zend_Registry::set('stack_trace', $re);
        Utils_Request::sendHttpStatusCode($re->httpStatusCode);
        try {
            FrontDispatcher::instance()->setConfig($this->_config)->render('rerror');
        }
        catch (Exception $e) {
            die('Cannot render request error page!');
        }
    }

    /**
     * Renders a 404 page not found error page
     *
     * View canonical name to render : `404'
     *
     * The application is responsible for providing such a page
     * in the available view paths (@see Utils_ResourceLocator::viewDirs()).
     * 404 HTTP status code is sent back to the client.
     * If the rerror view is not found, the script dies.
     */
    private function spawn404Error()
    {
        Utils_Request::sendHttpStatusCode(Utils_Request::CLIENT_NOT_FOUND);
        try {
            FrontDispatcher::instance()->setConfig($this->_config)->render('404');
        }
        catch (Exception $e) {
            die('Cannot render 404 page!');
        }
    }


    /**
     * Runs all initialization routines in order, then processes the request
     *
     * - Sets up the application namespace
     *   - Default namespace used is "cli" (command-line).
     *   - If NAMESPACE is defined, then it is used. Otherwise, if $_SERVER is defined
     *     (run context is through a web server) then SERVER_NAME is used.
     * - initRequest expects the PHP bootstrap filename that called Init in a
     *   previously defined constant BOOTSTRAP_FILE.
     * - parseRequest should always be the last method called.
     * - the init error page is rendered if a WrappedException is caught.
     *
     * @post The application namespace is registered in the Zend_Registry as 'namespace'
     */
    private function run()
    {
        error_reporting(E_ALL|E_STRICT);
        $namespace = 'cli';
        if (defined('NAMESPACE')) {
            $namespace = constant('NAMESPACE');
        }
        elseif (isset($_SERVER, $_SERVER['SERVER_NAME'])) {
            $namespace = $_SERVER['SERVER_NAME'];
        }
        if (!defined('BOOTSTRAP_FILE')) {
            die('Please define BOOTSTRAP_FILE before calling Init::run()');
        }
        Zend_Registry::set('namespace', $namespace);
        try {
            $this->startTimer();
            $this->loadConfig(str_replace(array('.','-'), '_', $namespace));
            $this->setDebug();
            $this->initResources();
            $this->initTimezone();
            $this->initRequest(constant('BOOTSTRAP_FILE'), $namespace);
            $this->initLocale();
            $this->initTranslate();
            $this->initLogging($namespace);
            $this->parseRequest($namespace);
        } catch (WrappedException $we) {
            $this->spawnInitError($we);
        }
    }


    /**
     * The general entry point to the application
     *
     * Creates a new instance of the Init class and runs it.
     * Calls :
     *   - preRun() : Processes the class PreRun if defined within the scope of the current application.
     *   - run() : main process
     */
    public static function main()
    {
        $init = new Init();
        $init->preRun();
        $init->run();
    }


    /**
     * Private ctor: only instantiable through main()
     */
    private function __construct()
    {
    }
}
