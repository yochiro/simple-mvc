<?php
final class Utils_Url
{
    public static function getRequestUrl()
    {
        return rtrim((isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:$_SERVER['REQUEST_URI']), '/').'/';
    }

    public static function getSiteLocalReferer()
    {
        $uri = null;
        if (isset($_SERVER['HTTP_REFERER'])) {
            $forward = rtrim(self::getSiteURI(),'/');
            $referer = $_SERVER['HTTP_REFERER'];
            $query_uri = strstr($referer, $forward);
            if (false !== $query_uri) {
                preg_match('!'.$forward.'([^?#]*)!', $query_uri, $query_uri);
                if (!empty($query_uri)) {
                    $uri = rtrim($query_uri[1], '/').'/';
                }
            }
        }
        return $uri;
    }

    public static function getSiteURI()
    {
        return self::getBase().ltrim(self::getSiteBase(),'/');
    }

    public static function getSiteBase()
    {
        $out = '';
        $ru = self::getRequestUrl();
        $pi = $_SERVER['PATH_INFO'];
        if (false !== ($s=strrpos($ru, $pi))) {
            $out .= rtrim(substr($ru, 0, $s), '/').'/';
        }
        return $out;
    }

    public static function getSiteRequest()
    {
        $out = '';
        $ru = self::getRequestUrl();
        $pi = $_SERVER['PATH_INFO'];
        if (false !== ($s=strrpos($ru, $pi))) {
            $out .= rtrim(substr($ru, $s), '/').'/';
        }
        return $out;
    }

    public static function getAltBase()
    {
        $host = (Zend_Registry::isRegistered('alt_namespace')?
                 Zend_Registry::get('alt_namespace'):null);
        if (is_null($host) || 'default' === $host) {
            return self::getBase();
        }
        $uri = '';
        $proto = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=="off") ? 'https' : 'http';
        $port  = (isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:80);
        $uri   = $proto . '://' . $host;
        if ((('http' == $proto) && (80 != $port)) || (('https' == $proto) && (443 != $port))) {
            $uri .= ':' . $port;
        }
        return $uri . '/';
    }

    public static function getBase()
    {
        $uri = '';
        $host  = (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
        $proto = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=="off") ? 'https' : 'http';
        $port  = (isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:80);
        $uri   = $proto . '://' . $host;
        if ((('http' == $proto) && (80 != $port)) || (('https' == $proto) && (443 != $port))) {
            $uri .= ':' . $port;
        }
        return $uri . '/';
    }

    public static function buildUri($url)
    {
        $uri = '';
        if (!preg_match('#^(https?|ftp)://#', $url)) {
            $uri = self::getBase();
        }
        $uri = $uri . ltrim($url, '/');
        return rtrim($uri,'/').'/';
    }
}
