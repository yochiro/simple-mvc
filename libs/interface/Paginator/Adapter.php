<?php
interface Iface_Paginator_Adapter
{
    static function accept($entity);

    function totalCount();

    function itemsInRange($limit, $offset);

    function __construct($entity);
}
