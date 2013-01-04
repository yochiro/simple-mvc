<?php
/**
* Returns PHP-evaled text as a partial inside a layout
*/

/**
* Returns the content after being evaled by PHP engine
*/
final class Savant3_Plugin_partial extends Savant3_Plugin
{
    public function partial($str)
    {
        ob_start();
        eval('?>'.$str);
        return ob_get_clean();
    }

    public function __isset($field)
    {
        return isset($this->Savant->$field);
    }

    public function __get($field)
    {
        return $this->Savant->$field;
    }

    public function __call($method, $params)
    {
        return call_user_func_array(array($this->Savant, $method), $params);
    }
}
