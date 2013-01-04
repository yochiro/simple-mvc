<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Wrapper for MECAB
 *
 * @package smvc
 */
final class Utils_Mecab
{
    const NODE_FORMAT = '%m,%t,%ps,%pl;';
    const EOS_FORMAT = '';

    private $_mecabTagger = null;


    public function __construct()
    {
        $argFormat = array('--node-format'=>self::NODE_FORMAT,
                           '--eos-format'=>self::EOS_FORMAT);
        $this->_mecabTagger = new Mecab_Tagger($argFormat);
    }

    public static function parseToArray($str)
    {
        $m = new self();
        $out = array();
        $parsedStr = $m->parse($str);
        $out = explode(';', $parsedStr);
        foreach ($out as $idx => $v) {
            $out[$idx] = explode(',', $v);
        }
        array_pop($out);
        return $out;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_mecabTagger, $method), $args);
    }
}
