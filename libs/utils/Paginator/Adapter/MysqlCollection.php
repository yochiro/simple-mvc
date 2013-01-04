<?php
final class Utils_Paginator_Adapter_MysqlCollection extends Utils_Paginator_Adapter
{
    private $_coll  = null;


    public static function accept($entity)
    {
        return ($entity instanceof Iface_MySQLAdapter);
    }


    public function __construct($entity)
    {
        $this->_coll = $entity;
    }


    protected function doTotalCount()
    {
        return $this->_coll->countSet();
    }

    protected function doItemsInRange($limit, $offset)
    {
        return $this->_coll->findSet($limit, $offset);
    }
}
