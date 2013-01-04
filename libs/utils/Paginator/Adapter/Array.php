<?php
final class Utils_Paginator_Adapter_Array extends Utils_Paginator_Adapter
{
    private $_coll = null;

    public static function accept($entity)
    {
        return is_array($entity);
    }


    public function __construct($entity)
    {
        $this->_coll = $entity;
    }

    protected function doTotalCount()
    {
        return count($this->_coll);
    }

    protected function doItemsInRange($limit, $offset)
    {
        return array_slice($this->_coll, $offset, $limit);
    }
}
