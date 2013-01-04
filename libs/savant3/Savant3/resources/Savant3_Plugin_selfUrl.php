<?php
/**
* Returns URI to current page
*/

/**
* Generates URI to current page
*/
final class Savant3_Plugin_selfUrl extends Savant3_Plugin
{
    public function selfUrl($keepParams=array(), $addParams=array())
    {
        $url   = (isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:'');
        parse_str(isset($_SERVER['REDIRECT_QUERY_STRING'])?$_SERVER['REDIRECT_QUERY_STRING']:
                  $_SERVER['QUERY_STRING'], $params);
        $query_p = array();
        foreach ($keepParams as $param) {
            if (isset($params[$param])) {
                $query_p[$param] = $params[$param];
            }
        }
        foreach ($addParams as $name=>$val) {
            $query_p[$name] = $val;
        }

        $url = Utils_Url::buildUri($url);
        if (!empty($query_p)) {
            $url.='?'.http_build_query($query_p, '', '&amp;');
        }
        return $url;
    }
}


