<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines an interface for a controller that can handle ajax requests
 *
 * Ajax controller has two methods, isXmlHttpRequest and getXmlHttpResponse.
 * isXmlHttpRequest returns true|false whether current request is an Ajax request.
 * getXmlHttpResponse should return the JSON response to be sent back to the client
 * should the controller successfully complete its process.
 *
 * @package smvc
 */
interface Iface_AjaxController
{
    /**
     * Returns whether current request is an Ajax request
     *
     * @return true|false is current request an Ajax request ?
     */
    function isXmlHttpRequest();

    /**
     * Returns JSON response (if any) to send back to the client
     *
     * @return string the json response
     */
    function getXmlHttpResponse();
}
