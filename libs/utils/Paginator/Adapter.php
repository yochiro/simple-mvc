<?php
abstract class Utils_Paginator_Adapter implements Iface_Paginator_Adapter
{
    final public function totalCount()
    {
        return $this->doTotalCount();
    }

    final public function itemsInRange($limit, $offset)
    {
        return $this->doItemsInRange($limit, $offset);
    }


    abstract protected function doTotalCount();
    abstract protected function doItemsInRange($limit, $offset);
}
