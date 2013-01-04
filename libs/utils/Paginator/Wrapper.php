<?php
abstract class Utils_Paginator_Wrapper implements Iface_Paginator_Wrapper,
                                                  Iterator, Countable, ArrayAccess
{
    public function __construct(Iface_Paginator_Adapter $collection = null, $page = 0, $fetchSize = 0)
    {
        $this->setCollection($collection);
        $this->setPage($page);
        $this->setFetchSize($fetchSize);
    }


    final public function setCollection(Iface_Paginator_Adapter $adapter)
    {
        $this->doSetCollection($adapter);
        return $this;
    }

    final public function setPage($pageNb)
    {
        $this->doSetPage($pageNb);
        return $this;
    }

    final public function setFetchSize($fetchNb)
    {
        $this->doSetFetchSize($fetchNb);
        return $this;
    }

    final public function page()
    {
        return $this->doPage();
    }

    final public function fetchSize()
    {
        return $this->doFetchSize();
    }

    final public function fetchCount()
    {
        return $this->doFetchCount();
    }

    final public function totalCount()
    {
        return $this->doTotalCount();
    }

    final public function pageCount()
    {
        return $this->doPageCount();
    }

    final public function offset()
    {
        return $this->doOffset();
    }

    final public function fetchItems()
    {
        return $this->doFetchItems();
    }

    final public function hasItems()
    {
        return $this->doHasItems();
    }

    final public function getFirstInPage()
    {
        return $this->doGetFirstInPage();
    }

    final public function getLastInPage()
    {
        return $this->doGetLastInPage();
    }

    final public function getRange($rangeSize)
    {
        return $this->doGetRange($rangeSize);
    }

    final public function hasNext()
    {
        return $this->doHasNext();
    }

    final public function hasPrevious()
    {
        return $this->doHasPrevious();
    }


    protected function doPageCount()
    {
        return intval(ceil($this->totalCount()/$this->fetchSize()));
    }

    protected function doOffset()
    {
        return ($this->fetchSize()*($this->page()-1));
    }

    protected function doHasItems()
    {
        return (0 < $this->totalCount());
    }

    protected function doGetFirstInPage()
    {
        return $this->offset()+1;
    }

    protected function doGetLastInPage()
    {
        return $this->getFirstInPage()+$this->fetchCount();
    }

    protected function doGetRange($rangeSize)
    {
        $currPage = $this->page();
        $pageCnt  = $this->pageCount();
        $halfSize = intval(ceil($rangeSize/2));
        $offR     = ($currPage<=$halfSize)?($halfSize-$currPage+1):0;
        $offL     = (($pageCnt-$currPage)<$halfSize)?($halfSize-($pageCnt-$currPage)):0;
        $low = ($currPage - $halfSize - $offL) > 0 ?
               ($currPage - $halfSize - $offL) : 1;
        $high = ($currPage + $halfSize + $offR) < $pageCnt ?
                ($currPage + $halfSize + $offR) : $pageCnt;
        return range(intval($low), intval($high));
    }

    protected function doHasNext()
    {
        return ($this->page() < $this->pageCount());
    }

    protected function doHasPrevious()
    {
        return (1 < $this->page());
    }


    abstract protected function doSetCollection(Iface_Paginator_Adapter $coll);
    abstract protected function doSetPage($pageNb);
    abstract protected function doSetFetchSize($fetchNb);
    abstract protected function doPage();
    abstract protected function doFetchSize();
    abstract protected function doTotalCount();
    abstract protected function doFetchCount();
    abstract protected function doFetchItems();
}
