<?php
final class Utils_Cache
{
    private static $_prefix = '';


    public static function setPrefix($prefix)
    {
        self::$_prefix = $prefix . '__';
    }

    public static function fetchFromFile()
    {
        return @file_get_contents(self::getFile());
    }

    public static function saveToFile($buffer)
    {
        file_put_contents(self::getFile(), $buffer, LOCK_EX);
        return $buffer;
    }

    public static function deleteFiles($request=null, $namespace=null)
    {
        $fPattern = self::getFilePrefix($request, $namespace).'*';
        $fToDel = glob(Utils_ResourceLocator::cacheDir().$fPattern);
        foreach ($fToDel as $f) {
            @unlink($f);
        }
    }

    public static function getFile()
    {
        return Utils_ResourceLocator::cacheFile(self::getFileName());
    }

    public static function isInCache()
    {
        return (!is_null(Utils_ResourceLocator::cacheFile(self::getFileName(), true)));
    }

    public static function getFileName()
    {
        $params = Utils_Request::getParams();
        $params = implode('_', array_map(create_function('$k,$v','return $k."=".$v;'), array_keys($params), $params));
        return self::getFilePrefix().md5($params);
    }

    public static function getFilePrefix($request=null, $namespace=null)
    {
        if (is_null($namespace)) {
            $namespace  = Zend_Registry::get('namespace');
        }
        if (is_null($request)) {
            $request = trim(Utils_Url::getRequestUrl(),'/');
        }
        $request = str_replace('/','_',trim($request,'/'));
        return self::$_prefix.$namespace.$request.'__';
    }
}
