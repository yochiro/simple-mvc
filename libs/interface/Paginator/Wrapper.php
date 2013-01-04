<?php
interface Iface_Paginator_Wrapper
{
    function __construct(Iface_Paginator_Adapter $collection=null, $page = 0, $fetchSize = 0);

    function setCollection(Iface_Paginator_Adapter $adapter);

    function setPage($pageNb);

    function setFetchSize($fetchNb);

    function page();

    function fetchSize();

    function totalCount();

    function fetchCount();

    function pageCount();

    function offset();

    function fetchItems();

    function hasItems();

    function getFirstInPage();

    function getLastInPage();

    function getRange($rangeSize);

    function hasNext();

    function hasPrevious();
}
