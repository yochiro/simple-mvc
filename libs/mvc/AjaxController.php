<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines a base class for ajax handling controllers
 *
 * Non ajax requests are delegated to the SimpleController class.
 *
 * @package SimpleMVC
 */
abstract class AjaxController extends SimpleController
                           implements Iface_AjaxController
{
    /**
     * @see Iface_AjaxController::isXmlHttpRequest
     */
    final public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /**
     * @see Iface_AjaxController::getXmlHttpResponse
     */
    final public function getXmlHttpResponse()
    {
        return $this->doGetXmlHttpResponse();
    }

    /**
     * @see SimpleController:doDispatch
     *
     * Handles AJAX requests, while delegating the rest to parent.
     * If no internal forward is requested, a JSON response is sent back
     * upon completion, and this method doesn't return (script is ended).
     * Otherwise, process is given back to the FrontDispatcher upon return.
     *
     * If an exception is raised during the controller request process,
     * the exception message is sent back as the JSON response.
     */
    protected function doDispatch()
    {
        $msg = null;
        if ($this->isXmlHttpRequest()) {
            try {
                $ret = $this->doAjaxDispatch();
            } catch (Exception $e) {
                $msg = $e->getMessage();
            }
        }
        else {
            parent::doDispatch();
        }
        if (!$this->isForward()) {
            // No return value needed, as we exit after sending json response
            $this->sendJsonResponse($msg);
        }
        return true;
    }


    /**
     * Sends specified JSON content to client
     *
     * Uses content returned by getXmlHttpResponse as content to be sent
     * if $msg parameter is omitted or null.
     * exits at the end.
     *
     * Depends on Zend_Json for endoding.
     *
     * @param string $msg the message to convert and send as JSON
     */
    private function sendJsonResponse($msg=null)
    {
        $msg = (is_null($msg)?$this->getXmlHttpResponse():$msg);
        if (!is_null($msg)) {
            header('Content-Type: application/json');
            echo Zend_Json::encode($msg);
        }
        exit(0);
    }


    /**
     * Processes the Ajax request
     * @see SimpleController::doPostDispatch()
     */
    abstract protected function doAjaxDispatch();

    /**
     * Message to be sent as JSON to client upon controller process completion
     *
     * Each concrete controller implementation must implement this.
     *
     * @return string the message to be converted to JSON and sent to client
     */
    abstract protected function doGetXmlHttpResponse();
}
