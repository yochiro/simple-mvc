<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Associates an exception with a generic error message
 *
 * This wrapper is used to provide generic error messages to display to end
 * users without losing the original exception to display to developers (when
 * debugging is on).
 *
 * @package smvc
 */
final class WrappedException extends Exception
{
    /** @var Exception $e the wrapped exception */
    protected $_e;

    /**
     * Sets the generic error message and wrapped exception
     *
     * @param string $msg the generic error message
     * @param Exception $e the exception to wrap
     */
    public function __construct($msg, $e)
    {
        $this->message = $msg;
        $this->_e = $e;
    }

    /**
     * Returns the wrapped exception
     *
     * @return Exception the wrapped exception
     */
    public function getException()
    {
        return $this->_e;
    }

}

