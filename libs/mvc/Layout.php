<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines the layout templating engine used to render views
 *
 * It wraps the Savant3 templating engine. (http://phpsavant.com)
 * Savant3 filters and plugins can be used (refer to the savant3 documentation for more details).
 *
 * [About templates]
 * Template files are phtml (HTML files with PHP embedded code).
 * Template objects have metadata accessible through the object notation (->). eg.:
 * @code
 * $layout->foo
 * $layout->foo = 'bar';
 * @endcode
 * They are available in the PHTML code through $this.
 * They also have a view object (Iface_View) embedded that can be assigned using setView.
 * Views can contain PHP code, which is interpreted before the layout is rendered.
 * The embedded view can add its own specific metadata by specifying them in the YAML header (if any).
 * Otherwise, every template will have at least the following basic metadata available :
 *   - locale : the current application locale (eg. en_US, ja_JP)
 *   - content : the view content after its PHP has been eval'ed.
 *   - view : the Iface_View embedded view object.
 *   - __name : the view canonical name (set by SimpleMVCFactory::view)
 *   - __namespace : the namespace used for the view, when multiple candidates are available (set by SimpleMVCFactory::view)
 * By default, the view content won't be rendered in the layout unless explicitely specified in the template code.
 * The view content can be displayed at the specified location by echoing $this->content in the template phtml.
 *
 * [Files Locations]
 * - Templates locations are retrieved through Utils_ResourceLocator::layoutDirs(). Templates locations
 * are namespaced, so different templates can be applied to the same view depending on the current application.
 * - Plugins and filters are located either in the framework or the application libs directory :
 *   COMMONROOT|APPROOT/libs/savant3/Savant3/resources/
 * Filter and Plugin classes are resolved from names : eg. foo => Savant3_Filter_Foo, bar => Savant3_Plugin_Bar.
 * addGlobalFilters allows to set filters to be applied to all views.
 * Embedded views can have their own filters to be applied, defined in the YAML header.
 * Rendering is usually done by the view object, the latter delegating the process to the layout.
 *
 * [Usage example]
 * @code
 * $view = MVC::view('bar');
 * $layout = new Layout();
 * $layout->setTemplate('foo');
 * $layout->setView($view);
 * $layout->render();
 * @endcode
 * Will render the view identified by 'bar' onto the template called 'foo'.
 * The view can optionally be passed to the render method instead.
 * Also, the interface is fluent, so most methods can be chained :
 * @code
 * $layout = Layout::create()->setTemplate('foo')->render(MVC::view('bar'));
 * @endcode
 * will do the same as the previous code.
 *
 * As said above, the following would usually be done if the view headers provide a layout property :
 * @code
 * $view = MVC::view('bar'); // The YAML header of specified view defines layout: foo
 * $view->render(); // Creates a Layout and delegates rendering to it
 * @endcode
 *
 * @package SimpleMVC
 */
final class Layout
{
    /**
     * Static list of global filters
     * @var array
     */
    private static $_gFilters = array();

     /**
     * The savant3 template to render
     * @var Savant3
     */
    private $_savantTpl = null;

    /**
     * The view to render into the template
     * @var Iface_View
     */
    private $_view = null;


    /**
     * Adds the list of specified filters to the filter list
     *
     * These filters will be all processed as the template is rendered.
     * Views can defined additional filters as needed.
     * Filters must be assigned a priority : they will be processed in the order
     * of descending priority.
     * This can be useful if some filters require a specific output from a previous
     * filter to work.
     * Parameter format is :
     * array('filter1' => prio (integer),
             'filter2' => prio (integer)
             ...)
     * filter1 and filter2 will be resolved to the actual filter classname as
     * Savant3_Filter_Filter1 and Savant3_Filter_Filter2
     * Filter classes can be located either in the framework dir or the application libs dir.
     * Location is set by the ctor of the Layout and is defined as :
     * LIB_DIR|APP_DIR/libs/savant3/Savant3/resources/. Any filter/plugin with the correct classname
     * located in either directory will automatically be retrievable by the template.
     *
     * @param array $filters list of filters to add
     */
    public static function addGlobalFilters($filters)
    {
        foreach ($filters as $f=>$prio) {
            self::$_gFilters[$prio][] = array('Savant3_Filter_'.ucfirst($f), 'filter');
        }
    }

    /**
     * Static helper to return a new Layout object. Used for chaining.
     *
     * @return Layout a new Layout instance
     */
    public static function create()
    {
        return new Layout();
    }


    /**
     * Ctor.
     *
     * Lookup paths for PHTML template files, plugins and filters is initialized.
     * locale metadata is assigned to the new template.
     */
    public function __construct()
    {
        $tpl = new Savant3();
        // Savant3 prepends them to the list of paths : must be reversed
        foreach (array_reverse(Utils_ResourceLocator::layoutDirs()) as $dir) {
            $tpl->addPath('template', $dir);
        }
        $tpl->addPath('resource', Utils_ResourceLocator::libDir() . 'libs/savant3/Savant3/resources');
        if (is_dir(Utils_ResourceLocator::baseDir().'libs/savant3/Savant3/resources')) {
            $tpl->addPath('resource', Utils_ResourceLocator::baseDir() . 'libs/savant3/Savant3/resources');
        }
        $tpl->locale = Utils_LocaleSelector::instance()->getLocaleString();
        $this->_savantTpl = $tpl;
    }

    /**
     * Sets specified view
     *
     * @param Iface_View $view
     * @return $this for chaining
     */
    public function setView(Iface_View $view)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * Renders the layout using optional $view, or previously set one
     *
     * A view must be set in order to render something. If none,
     * an exception is thrown.
     * The view can be supplied either as this method parameter,
     * or through setView prior to calling this method.
     *
     * The view metadata are extracted (YAML header).
     * Layout recognizes the following attributes :
     * - [Required] layout: template name => 'foo' Translates to template foo.phtml.
     * - [Optional] filters: list of additional filters to apply to this view
     *   eg. filters:
     *         - foo
     *         - bar
     *   or  filters: foo   (single filter)
     * View filters priority is fixed and cannot be changed : 50.
     * Global filters with a priority higher than 50 will be processed before
     * The view filters, while those with a priority less than 50 will be processed after.
     * - [Optional] Any other metadata defined in the view will be stored as a template metadata and will
     * thus be accessible during the template rendering in the template .phtml file.
     * There is theoretical limit on the number of metadata a view can contain.
     * Anything YAML supports is a valid metadata.
     * Two additional metadata, content and view are assigned to the template before rendering begins.
     *
     * Rendering is buffered then output, which means render does not return the generated content.
     * Output buffering of the method may be used to retrieve the content in a string.
     *
     * @param Iface_View $view the optional view to render. Supersedes internal stored view if passed
     * @return $this for chaining
     * @throws Exception if no view is assigned to the template
     */
    public function render(Iface_View $view = null)
    {
        if (is_null($this->_view) && is_null($view)) {
            throw new Exception('Must set view before rendering');
        }
        if (is_null($view)) {
            $view = $this->_view;
        }

        $filters = self::$_gFilters;
        foreach ($view->meta() as $k => $v)
        {
            if ('layout' === $k) {
                $this->_savantTpl->setTemplate($v.'.phtml');
            }
            elseif ('filters' === $k) {
                if (is_array($v)) {
                    foreach ($v as $f) {
                        $filters[50][] = array('Savant3_Filter_'.ucfirst($f), 'filter');
                    }
                }
                else {
                    $filters[50][] = array('Savant3_Filter_'.ucfirst($v), 'filter');
                }
            }
            else {
                $this->_savantTpl->$k = $v;
            }
        }
        krsort($filters);
        foreach (array_values($filters) as $f) {
            call_user_func_array(array($this->_savantTpl, 'addFilters'), $f);
        }
        /* Some common attributes */
        $this->_savantTpl->view = $view;
        $this->_savantTpl->content = $this->_savantTpl->partial($view->content());

        $this->_savantTpl->display();
        return $this;
    }
}
