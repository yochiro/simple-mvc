<?php
/**
* Returns the view page title
*/

/**
* Returns the title of the current view
*/
final class Savant3_Plugin_title extends Savant3_Plugin
{
    public function title(Iface_View $v=null)
    {
        if (is_null($v)) {
            $v = $this->Savant->view;
        }

        $title  = '';
        $title .= isset($v->title)?$this->Savant->xl8($v->title):'';
        $title .= isset($v->subtitle)?$this->Savant->xl8($v->subtitle):'';
        return $this->Savant->escape($title);
    }
}


