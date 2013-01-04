<?php
/**
* Returns items located into specified menu type
*/

/**
* Returns the views whose "location" property is of specified type
*/
final class Savant3_Plugin_menu extends Savant3_Plugin
{
    public function menu($type=null)
    {
        // Simulate Closures for PHP < 5.3
        $f = PhpClosure::get(array('type'=>$type),
                             create_function('$c,$v', '
                             return (is_null($c->type) ||
                                    (isset($v->location) && $c->type === $v->location));'));

        $o = create_function('$v,$w', 'return ((isset($v->order)?$v->order:0) - (isset($w->order)?$w->order:0));');

/*      PHP 5.3
        $f = function($v) use ($type) {
                            return (is_null($type) ||
                                   (isset($v->location) && $type === $v->location) ||
                                   ($v->parent() && isset($v->parent()->location) &&
                                   $type === $v->parent()->location); };
*/
        return Utils_Views::filter($f, $o);
    }
}
