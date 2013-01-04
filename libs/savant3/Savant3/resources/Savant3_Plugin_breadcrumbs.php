<?php
/**
* Returns a breadcrumb from current page
*/

/**
* Returns the breadcrumb for current page
*
* Get all parents until we get to the root
*/
final class Savant3_Plugin_breadcrumbs extends Savant3_Plugin
{
    const BC_SEPARATOR = '&gt;&gt;&nbsp;';


    public function breadcrumbs()
    {
        $currview = $this->Savant->view;
        $bc = array();
        while (!$currview->isRoot()) {
            $bc[] = $currview;
            $currview = $currview->parent();
        }
        return array_reverse($bc);
    }
}
