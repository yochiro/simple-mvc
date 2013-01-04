<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * This class defines an abstraction for a resource loader.
 * load is the main function called by ResourceLoader: it is passed
 * the resource filename and its mimetype.
 * The Loader is expected to output the resource content to the buffered output
 * so nothing is returned.
 *
 * Each Loader should be a singleton. Nothing is implemented at this
 * level, so each loader should implement its own singleton behavior.
 *
 * @package smvc
 */
abstract class Utils_ResourceLoader_Abstract implements Iface_Singleton
{
    /**
     * Loads and output specified resource
     *
     * @param string $resource the resource to load
     * @param string $mime the resource mimetype
     */
    final public function load($resource, $mime)
    {
        $this->doLoad($resource, $mime);
    }


    abstract protected function doLoad($resource, $mime);
}
