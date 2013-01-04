<?php
/**
* Displays the rendering time for current request
*/

/**
* Renders as an HTML comment the rendering time for current request
*/
final class Savant3_Plugin_timer extends Savant3_Plugin
{
    public function timer()
    {
        $t = Zend_Registry::get('timer');
        $t->stop('page');
        return '<!-- Request time : ' . $t->__toString() . 'ms -->';
    }
}
