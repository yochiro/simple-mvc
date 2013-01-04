<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 *
 */

/**
 * Controller to handle script loading
 *
 * The controller just adds params to the Registry, so that
 * the related scripts view can extract all requested script names
 * and include them in a single request.
 */
final class ScriptsController extends SimpleController
{
    protected function doGetDispatch()
    {
        FrontDispatcher::instance()->addHeader('Content-type', 'text/javascript; charset=UTF-8')
                                   ->addHeader('Cache-control', 'max-age=290304000, public')
                                   ->addHeader('Pragma', 'public');
        // Params is a & separated list of js files to load (wo the .js)
        $this->addRequestData('params', $this->getParams());
    }
}
