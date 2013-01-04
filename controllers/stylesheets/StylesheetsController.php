<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 *
 */

/**
 * Controller to handle stylesheet loading
 *
 * The controller just adds params to the Registry, so that
 * the related stylesheets view can extract all requested stylesheet names
 * and include them in a single request.
 */
final class StylesheetsController extends SimpleController
{
    protected function doGetDispatch()
    {
        FrontDispatcher::instance()->addHeader('Content-type', 'text/css; charset=UTF-8')
                                   ->addHeader('Cache-control', 'max-age=290304000, public')
                                   ->addHeader('Pragma', 'public');
        // Params is a & separated list of css (php) files to load (wo extension)
        $this->addRequestData('params', $this->getParams());
    }
}
