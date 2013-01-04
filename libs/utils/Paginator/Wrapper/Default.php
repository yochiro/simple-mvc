<?php
final class Utils_Paginator_Wrapper_Default extends Utils_Paginator_Wrapper
{
    private $_coll = null;
    private $_pageNb = 0;
    private $_fetchSize = 0;
    private $_fetchedItems = array();


    final public function offsetExists($offset)
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        return array_key_exists($offset, $this->_fetchItems[$key]);
    }

    final public function offsetGet($offset)
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        return $this->_fetchItems[$key][$offset];
    }

    final public function offsetSet($offset, $value)
    {
        // not accepted
        throw exception('operation not supported!');
    }

    final public function offsetUnset($offset)
    {
        // not accepted
        throw exception('operation not supported!');
    }

    final public function count()
    {
        return $this->totalCount();
    }

    final public function current()
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        return current($this->_fetchedItems[$key]);
    }

    final public function key()
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        return key($this->_fetchedItems[$key]);
    }

    final public function next()
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        next($this->_fetchedItems[$key]);
    }

    final public function rewind()
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        reset($this->_fetchedItems[$key]);
    }

    final public function valid()
    {
        $key = $this->_getCacheKey();
        $this->fetchItems();
        return (current($this->_fetchedItems[$key]) !== false);
    }


    protected function doSetCollection(Iface_Paginator_Adapter $coll)
    {
        $this->_coll = $coll;
    }

    protected function doSetPage($pageNb)
    {
        $this->_pageNb = $pageNb;
    }

    protected function doSetFetchSize($fetchNb)
    {
        $this->_fetchSize = $fetchNb;
    }

    protected function doPage()
    {
        return $this->_pageNb;
    }

    protected function doFetchSize()
    {
        return $this->_fetchSize;
    }

    protected function doFetchCount()
    {
        return count($this->fetchItems());
    }

    protected function doTotalCount()
    {
        return $this->_coll->totalCount();
    }

    protected function doFetchItems()
    {
        $limit = $this->fetchSize();
        $offset = $this->offset();
        $cacheKey = $this->_getCacheKey();
        $out = array();
        if (!isset($this->_fetchedItems[$cacheKey])) {
            $out = $this->_coll->itemsInRange($limit, $offset);
            $this->_fetchedItems[$cacheKey] = $out;
        }
        else {
            $out = $this->_fetchedItems[$cacheKey];
        }
        return $out;
    }


    private function _getCacheKey()
    {
        return $this->fetchSize().','.$this->offset();
    }
}
