<?php
final class Utils_Paginator
{
    private static $_adapters = array();

    public static function get($entity, $page = 0, $fetchSize = 0, $type = 'default')
    {
        $wrapper = null;
        $cls = 'Utils_Paginator_Wrapper_'.ucfirst($type);
        if (class_exists($cls) && (in_array('Iface_Paginator_Wrapper', class_implements($cls)))) {
            $adapter = self::_adapter($entity);
            $wrapper = (!is_null($adapter))?new $cls($adapter, $page, $fetchSize):null;
        }
        return $wrapper;
    }

    public static function register($adapter)
    {
        $cls = 'Utils_Paginator_Adapter_'.ucfirst($adapter);
        if (class_exists($cls) && (in_array('Iface_Paginator_Adapter', class_implements($cls)))) {
            self::$_adapters[] = $cls;
        }
    }


    private static function _adapter($entity)
    {
        foreach (self::$_adapters as $adapter) {
            if (call_user_func(array($adapter, 'accept'), $entity)) {
                return new $adapter($entity);
            }
        }
        return null;
    }
}
