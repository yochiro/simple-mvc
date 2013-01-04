<?php
/**
* Returns an i18ed string for given key
*/

/**
* Returns an i18ed string for specified key
*/
final class Savant3_Plugin_xl8 extends Savant3_Plugin
{
    public function xl8($str, $escape=true)
    {
        static $xl8 = null;
        if (is_null($xl8)) {
            $xl8 = Zend_Registry::get('Zend_Translate');
        }
        $str = $xl8->_($str);
        if ($escape) {
            $str = $this->Savant->escape($str);
        }
        return $str;
    }
}
