<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines a simple implementation of a view
 *
 * SimpleView is meant to be embedded into Layout.
 * The content comes from a phtml file located in view paths (@see Utils_ResourceLocator::viewDirs()).
 * The views folder is namespaced, ie. each application can define in its own namespace folder views
 * defined with similar view URLs. (overrides in the config file)
 * A new view instance is returned by SimpleMVCFactory::view($viewname) which is passed the view canonical name :
 * it is mapped to an actual phtml file located in either view folder.
 * SimpleView gets a view canonical name, locates and loads the associated view file name if found, parses its content
 * (optional header included), and makes it available for Layout objects for rendering.
 *
 * [View organization]
 * This View implementation works using a hierarchy of folders, defining a parent-child relationship between
 * views. Eg: Top page > Categories > Subcategories > Detail page
 * "/" is used as the separator; thus, "/" refers to the top of the hierarchy, while name "/foo/" defines a view named "foo"
 * descendent of top view.
 *
 * [View canonical names to view files mapping]
 * A mapping is performed between the view name and the filename for this view, using the following rules,
 * starting with current application namespace, then going up the namespace while defined by "overrides".
 * The process below will try to locate the view /foo/bar :
 * - Try to map the view name to the directory hierarchy /foo/bar under the current namespace's view root folder
 *   - If found, check existence of a file named index.phtml : /foo/bar/index.phtml
 *     - If found, requested view = /foo/bar/index.phtml [END]
 *     - If not found, go back one folder up (/foo) and look for a file named bar.phtml
 *       - If found, requested view = /foo/bar.phtml [END]
 *       - If not found, requested view = null [ERROR, view does not exist]
 *   - If not found, requested view = null [ERROR, view does not exists]
 *
 * Files other than index.phtml contained in a given folder will have a child relationship
 * with index.phtml (the parent) located in that same folder. The latter will have the
 * upper level index.phtml as its parent.
 *
 * The example below show how the folder hierarchy determines the parent-child relationship
 * for each view, as well as how their name translates into
 * Legend: filename [parent=filename] [children=filenames] => view name
 * Eg.:
 * /
 * |_ index.phtml [parent=null] [children=/foo/index.phtml, /foo2/index.phtml] => /
 * |_ error.phtml [parent=null] [children=null] => /error/
 * |_ 404.phtml [parent=null] [children=null] => /404/
 * |_ foo
 * |  |_ index.phtml [parent=/index.phtml] [children=/foo/baz/index.phtml] => /foo/
 * |  |_ bar.phtml [parent=/foo/index.phtml] [children=null] => /foo/bar/
 * |  |_ baz
 * |     |_ index.phtml [parent=/foo/index.phtml] [children=null] => /foo/baz/
 * |     |_ foz.phtml [parent=/foo/baz/index.phtml] [children=null] => /foo/baz/foz/
 * |
 * |_ foo2
 *    |_ index.phtml [parent=/index.phtml] [children=null] => /foo/foo2/
 *
 * The root view can thus be retrieved using MVC::view('/').
 * Descendents are accessed through the children() method, which is lazily evaluated :
 * the children are not actually loaded until the first call to that method.
 * The same goes with the parent() method.
 *
 * [View metadata]
 * Each phtml file defined as a view can have an optional header.
 * Should a header be defined, <strong>it must appear at the top of file</strong>.
 * A header is defined using the YAML syntax.
 * Start of header (Should be line 1 in the view file) and its end is defined by "---".
 * Everything in between these two delimiters will be parsed as YAML content.
 * The header contains a list of YAML declarations of the form property: value.
 * Any valid YAML value can be used.
 * Each property (metadata) defined in the header will be stored in the SimpleView object
 * and its value accesssible through the object notation (->).
 * Example:
 * @code
 * ---
 * layout: default
 * location: menu
 * order: 1
 * show: true
 * title: My view title
 * filters:
 *   - filter1
 *   - filter2
 * ---
 * @endcode
 * Among all metadata defined in the example above, only 'layout' is <strong>mandatory</strong>.
 * It tells the Layout object which template to use for rendering (@see Layout).
 * When SimpleView::render() is called, the Layout will be rendered, with the view content where the
 * layout specifies it (if specified).
 * filters is also a reserved property name recognized by Layout : it defines a list of Savant3 filters
 * to apply during the view rendering (@see Layout, or refer to the Savant3 documentation).
 * Two other properties, __name and __namespace are stored by SimpleMVCFactory::view which refer to
 * resp. the view canonical name and the namespace for this view (in case some views override others
 * in different namespaces, this tells which one is actually used).
 * Aside from these reserved values, all other properties are user defined and can be accessed using eg.:
 * @code
 * echo $view->title;
 * if ($view->show) { echo $view->order . '. ' . $view->title; }
 * @endcode
 *
 * Note:
 * the phtml template and the phtml content of the view can access these metadata directly.
 * When the template requests it, the view content is embedded into the rendered page; PHP code
 * in the view phtml is executed <strong>within the context of the template</code>, ie.
 * $this actually refers to the Savant3 template; therefore,
 * $this->title is equivalent to $this->view->title (view is a property always defined in every template)
 * in the template phtml or the view phtml.
 *
 * @package smvc
 */
final class SimpleView implements Iface_View
{
    /**
     * This view children
     *
     * null until first call to children(), then children are looked up and saved as an array
     * children() will store an empty array if there are no children.
     *
     * @var null|array children views
     */
    private $_children = null;

    /**
     * This view's parent
     *
     * null until first call to parent(), then parent is looked up and saved
     *
     * @var null|Iface_View the parent view
     */
    private $_parent = null;

    /**
     * The view metadata
     * @var array metadata
     */
    private $_meta = array();

    /**
     * The view filename
     * @string the filename
     */
    private $_filename = null;

    /**
     * The view content (RAW, with PHP code)
     * @var string
     */
    private $_content = null;

    /**
     * Metadata parsing is lazy-evaluated. Flag to tell whether header was parsed or not
     * @var boolean true if YAML header is already parsed
     */
    private $_parsed = false;

    /**
     * The layout used to render current view
     * @var Layout
     */
    private $_layout = null;


    /**
     * Factory for SimpleView. Allow chaining
     *
     * @param string $file the path to the view file name (not its URL)
     * @return SimpleView the new SimpleView instance
     */
    public static function create($file)
    {
        return new self($file);
    }


    /**
     * Ctor
     *
     * A view needs a file to be constructed.
     *
     * @param string $file the path to the view file name (not its URL)
     */
    public function __construct($file)
    {
        $this->_filename = $file;
    }

    /**
     * Returns the view file name
     *
     * @return string the view filename
     */
    public function filename()
    {
        return $this->_filename;
    }

    /**
     * Sets the view filename, overridding the previous one if any
     *
     * By setting/changing the filename, the view must reinitialize its data.
     *
     * @param string $file the filename to set
     * @return $this for chaining
     */
    public function setFilename($file)
    {
        $this->_filename = $file;
        return $this;
    }

    /**
     * Returns the canonical name of the view
     *
     * Eg.:
     * - cname(/foo/bar/baz.phtml) => /foo/bar/baz/
     * - cname(/foo/bar/index.phtml) => /foo/bar/
     *
     * @return the view base name
     */
    public function cname()
    {
        if (isset($this->__name)) {
            return $this->__name;
        }
        // If __name is not defined, try to find the canonical name from the filename
        $f = $this->baseFileName();
        $f = str_replace('.phtml', '', $f);
        $f = str_replace('index', '', $f);
        return rtrim($f, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the directory name the view is located in
     *
     * Using a hierarchy of views, dirname allows to know whether
     * a given view belong to the same level as its parent or
     * if it's a sub-level. Files in the same directory are considered
     * of being the same level.
     * Eg.
     * 1- dirname(/foo/bar/baz.phtml) => foo/bar (cname=/foo/bar/baz/)
     * 2- dirname(/foo/bar/index.phtml) => foo/bar (cname=/foo/bar/)
     * 3- dirname(/foo/bar.phtml) => foo (cname=/foo/bar/)
     * 4- dirname(/) => '' (empty string)
     *
     * 1 and 2 belong to the same hierarchy level, though their canonical name (cname) is different.
     * 2 and 3 do not belong to the same hierarchy level, though their canonical name (cname) is the same.
     *
     * @return string the directory of current view filename
     */
    public function dirname()
    {
        $f = $this->baseFileName();
        return trim(dirname($f), DIRECTORY_SEPARATOR);
    }

    /**
     * Returns whether specified view is a descendent of current view
     *
     * @param Iface_View $v the view to compare to $this
     * @return true|false true if $v is descendent of $this, false otherwise
     */
    public function isDescendent(Iface_View $v)
    {
        // A view is a descendent if the current view canonical name is entirely contained
        // from the start of specified view's canonical name.
        return (0 === strpos($this->cname(), $v->cname()));
    }

    /**
     * Tells whether current instance is at the top of the view hierarchy
     *
     * Top hierarchy canonical name is '/'.
     *
     * @return bool true if top of tree, false otherwise
     */
    public function isRoot()
    {
        return '' === $this->dirname();
    }

    /**
     * Returns the view parent, or null if no parent (top of hierarchy)
     *
     * Lazy-evaled : parent is looked up and set at the first call
     *
     * @return Iface_View the parent view (null if top of hierarchy)
     */
    public function parent()
    {
        if (is_null($this->_parent) && !$this->isRoot()) {
            $parent = (!isset($this->parentRef)?
                      dirname(str_replace('index.phtml', '', $this->baseFileName())):
                      $this->parentRef);
            // Parent view name should be :
            // current view canonical name + 1 folder level up (dirname)
            $this->_parent = MVC::view($parent);
        }
        return $this->_parent;
    }

    /**
     * Returns whether the view has children
     *
     * @return true|false true if has children, false otherwise
     */
    public function hasChildren()
    {
        return (0 < count($this->children()));
    }

    /**
     * Returns the view children, optionally sort/filtering them
     *
     * $o and $f are optional callback methods used resp. for sorting
     * and filtering.
     * Returned children will be sorted using $o, and views not matching
     * $f will be excluded from the returned array.
     * $o : function(SimpleView $v1, SimpleView $v2)
     *      - return: -1 if v1 should be before v2, 0 if equal, 1 if v2 should be before v1
     * $f : function(SimpleView $v)
     *      - return: true to keep $v in the output, false to exclude it
     *
     * This method is lazy-evaled, ie. children are only loaded and stored when it is called.
     * Similarily, children's children are not loaded until children() is called on each child object.
     * sort and filter functions need to be respecified for each of these calls.
     *
     * @param callback $o the sort callback
     * @param callback $f the filter callback
     * @return array view's children, optionally sorted and/or filtered
     */
    public function children($o=null, $f=null)
    {
        if (is_null($this->_children)) {
            $this->_children = array();
            $c = PhpClosure::get(array('r'=>$this->baseFileName()),
                 create_function('$c, $v',
                                 'return str_replace(\'index\', \'\',
                                 str_replace(\'.phtml\', \'\', $v.trim($c->r, DIRECTORY_SEPARATOR)));'));
            $froms = array_map($c, Utils_ResourceLocator::viewDirs());
            $views = array();
            foreach ($froms as $from) {
                if ($handle = @opendir($from)) {
                    while(false !== ($file = readdir($handle))) {
                        if (0 === strpos($file, '.')) { continue; }
                        if ((is_file($from.DIRECTORY_SEPARATOR.$file) &&
                            !Utils::str_endsWith($file, 'index.phtml')) ||
                            (is_dir($from.DIRECTORY_SEPARATOR.$file) &&
                             file_exists($from.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.'index.phtml'))) {
                            $cf = str_replace('.phtml', '', $file);
                            if (!in_array($this->cname().$cf, $views)) {
                                array_push($views, $this->cname().$cf);
                            }
                        }
                    }
                }
            }
            foreach ($views as $v) {
                $c = MVC::view($v);
                $c->_parent = (isset($c->parentRef)?MVC::view($c->parentRef):$this);
                $this->_children[] = $c;
            }
        }

        // Don't modify flyweighted instance : create new array from original
        $c = $this->_children;
        if (!is_null($o)) {
          uasort($c, $o);
        }

        if (!is_null($f)) {
            $c = array_filter($c, $f);
        }
        return $c;
    }

    /**
     * Returns all metadata of the view
     *
     * @return array[string=>mixed] view metadata
     */
    public function meta()
    {
        // Parse it if not done so already
        $this->parse();
        return $this->_meta;
    }

    /**
     * Adds specified key/value pair to the metadata list of current view
     *
     * @param string $k the property name to add
     * @param mixed $v the property value
     * @return $this for chaining
     */
    public function addMeta($k, $v)
    {
        $this->_meta[$k] = $v;
        return $this;
    }

    /**
     * Returns the RAW (unprocessed PHP) content of the view
     *
     * @return string the view content
     */
    public function content()
    {
        // Parse it if not done so already
        $this->parse();
        return $this->_content;
    }

    /**
     * Shortcut for addMeta
     * @see addMeta
     */
    public function __set($k, $v)
    {
        return $this->addMeta($k, $v);
    }

    /**
     * Returns value of specified metadata
     *
     * @param string metadata name to return
     * @return mixed metadata value
     */
    public function __get($k)
    {
        $this->parse();
        return $this->_meta[$k];
    }

    /**
     * Returns whether specified metadata is defined by the view
     *
     * @param string metadata name to lookup
     * @return boolean true if defined, false otherwise
     */
    public function __isset($k)
    {
        $this->parse();
        return array_key_exists($k, $this->_meta);
    }

    /**
     * Outputs current view content within its layout if any
     */
    public function render()
    {
        Layout::create()->setView($this)->render();
    }

    /**
     * Parses YAML header if not done so already
     *
     * As parsing is lazy evaled, a flag is used to
     * determine if it has been done or not.
     * If not, then load the view file content and feed
     * the YAML parser with it.
     * Each property defined in the YAML header will become
     * a metadata of the view.
     * content is also parsed and available as the content property.
     *
     * @post metadata defined in the header can be retrieved using $view->metadata
     * @post content can be retrieved using $view->content
     */
    private function parse()
    {
        if (false === $this->_parsed) {
            // parseFile returns an array [header definition, actual content]
            list($yamlDef, $content) = $this->parseFile();
            foreach (Spyc::YAMLLoadString($yamlDef) as $key => $val) {
                // i18n value strings begin with an underscore
                if (0 === strpos($val, '_')) {
                    $val = trim(substr($val, 1), '\'');
                }
                $this->addMeta($key, $val);
            }
            $this->_content = $content;
            $this->_parsed = true;
        }
    }


    // YAML_header : array[string key, mixed value]
    // content : string
    // @return array[YAML_header, content]
    private function parseFile()
    {
        $file = $this->_filename;
        $vals = array();
        $yamlDef = '';
        if (!empty($file)) {
            // the file content as an array (one entry per line)
            $vals = file($file);
            $yamlDelim = '---'.PHP_EOL;
            $yamlDef = '';
            // Only tries to extract header if first line was the "---" delimiter
            if (!empty($vals) && $yamlDelim === $vals[0]) {
                // remove current line from the file content array
                $yamlDef .= array_shift($vals);
                $val = null;
                while (($val = array_shift($vals)) && !is_null($val) && $yamlDelim !== $val) {
                    $yamlDef .= $val;
                }
                if ($yamlDelim !== $val) {
                    throw new Exception('Wrong YAML header for view : ' . $file);
                }
            }
        }
        // Sets whatever's left in the $vals array as the view content
        return array($yamlDef, implode('', $vals));
    }

    /**
     * Returns the view file base name.
     *
     * This function strips the docroot up to the view root folder.
     * eg. /var/www/docroot/app/views/namespace/foo/bar.phtml => /foo/bar.phtml
     *
     * Views root folder can currently be located in two different places :
     * - framework package
     * - application package
     * Strip anything until the canonical name of the view is found in the filename (from the end).
     *
     * @return string file base name
     */
    private function baseFileName()
    {
        $f = substr($this->filename(), strrpos($this->filename(), DIRECTORY_SEPARATOR.trim($this->cname(), DIRECTORY_SEPARATOR)));
        return $f;
    }
}
