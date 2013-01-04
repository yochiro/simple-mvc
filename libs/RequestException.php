<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines an exception for request errors
 *
 * An optional error status code can be assigned, to identify the request error
 * that raised this exception. Default is BAD_REQUEST.
 * Valid status codes are defined through Utils_Request constants.
 *
 * @package smvc
 */
class RequestException extends Exception
{
    /**
     * HTTP status code giving this exception
     * @var integer valid HTTP status code
     */
    public $httpStatusCode = Utils_Request::CLIENT_BAD_REQUEST;


    /**
     * Constructor
     *
     * @param string $msg The user friendly message to show
     * @param integer $httpStatusCode HTTP status code to set. Default is CLIENT BAD REQUEST
     */
    public function __construct($msg, $httpStatusCode = Utils_Request::CLIENT_BAD_REQUEST)
    {
        parent::__construct($msg);
        $this->httpStatusCode = $httpStatusCode;
    }
}
