<?php
final class Utils_Request
{
    const REDIRECT_MOVED_PERMANENTLY = 301;
    const REDIRECT_FOUND = 302;
    const REDIRECT_SEE_OTHER = 303;
    const REDIRECT_NOT_MODIFIED = 304;
    const REDIRECT_TEMPORARY_REDIRECT = 307;
    const CLIENT_BAD_REQUEST = 400;
    const CLIENT_UNAUTHORIZED = 401;
    const CLIENT_FORBIDDEN = 403;
    const CLIENT_NOT_FOUND = 404;
    const CLIENT_METHOD_NOT_ALLOWED = 405;
    const CLIENT_NOT_ACCEPTABLE = 406;
    const CLIENT_REQUEST_TIMEOUT = 408;
    const CLIENT_UNSUPPORTED_MEDIA_TYPE = 415;
    const SERVER_INTERNAL_SERVER_ERROR = 500;
    const SERVER_NOT_IMPLEMENTED = 501;
    const SERVER_BAD_GATEWAY = 502;
    const SERVER_SERVICE_UNAVAILABLE = 503;


    private static $_statusMsg = array(
            self::REDIRECT_MOVED_PERMANENTLY => 'Moved Permanently',
            self::REDIRECT_FOUND => 'Found',
            self::REDIRECT_SEE_OTHER => 'See Other',
            self::REDIRECT_NOT_MODIFIED => 'Not Modified',
            self::REDIRECT_TEMPORARY_REDIRECT => 'Temporary Redirect',
            self::CLIENT_BAD_REQUEST => 'Bad Request',
            self::CLIENT_UNAUTHORIZED => 'Unauthorized',
            self::CLIENT_FORBIDDEN => 'Forbidden',
            self::CLIENT_NOT_FOUND => 'Not Found',
            self::CLIENT_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::CLIENT_NOT_ACCEPTABLE => 'Not Acceptable',
            self::CLIENT_REQUEST_TIMEOUT => 'Request Timeout',
            self::CLIENT_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
            self::SERVER_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::SERVER_NOT_IMPLEMENTED => 'Not Implemented',
            self::SERVER_BAD_GATEWAY => 'Bad Gateway',
            self::SERVER_SERVICE_UNAVAILABLE => 'Service Unavailable');


    public static function getPost()
    {
        return array_map(create_function('$v', 'return Utils::sanitize($v);'), $_POST);
    }

    public static function getParams()
    {
        return array_map(create_function('$v', 'return Utils::sanitize($v);'), $_GET);
    }

    public static function getParam($name, $from=null)
    {
        $val = null;
        if ((is_null($from) || 'get' === strtolower($from)) &&
            array_key_exists($name, $_GET)) {
            $val = $_GET[$name];
        }
        elseif ((is_null($from) || 'post' === strtolower($from)) &&
            array_key_exists($name, $_POST)) {
            $val = $_POST[$name];
        }
        return Utils::sanitize($val);
    }

    public static function getRequestURI()
    {
        $url   = (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'');
        return Utils_Url::buildUri($url);
    }

    public static function getRequestMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function isPost()
    {
        return 'post' === self::getRequestMethod();
    }

    public static function isGet()
    {
        return 'get' === self::getRequestMethod();
    }

    public static function isSecure()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'?true:false);
    }

    public static function getScheme()
    {
        return (self::isSecure()?'https':'http');
    }

    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     */
    public static function getHeader($header)
    {
        $ret = null;

        if (!is_null($header)) {
            // Try to get it from the $_SERVER array first
            $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
            if (!empty($_SERVER[$temp])) {
                $ret = $_SERVER[$temp];
            }

            // This seems to be the only way to get the Authorization header on
            // Apache
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                if (!empty($headers[$header])) {
                    $ret = $headers[$header];
                }
            }
        }

        return $ret;
    }

    public static function sendHttpStatusCode($sc)
    {
        if (array_key_exists($sc, self::$_statusMsg)) {
            @header('HTTP/1.1 ' . $sc . ' ' . self::$_statusMsg[$sc]);
        }
    }
}
