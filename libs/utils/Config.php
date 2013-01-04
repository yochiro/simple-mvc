<?php
final class Utils_Config
{
    private $_yamlParser = null;
    private $_config = null;
    private $_configChain = array();


    public function __construct($filename, $section=null)
    {
        $this->_yamlParser = new Spyc();
        $config = $this->_yamlParser->loadFile($filename);
        $chain = array();
        while(!is_null($section) && isset($config[$section])) {
            $c = $config[$section];
            $chain[$section] = $c;
            $section = isset($c['overrides'])?$c['overrides']:null;
        }
        $c = array();
        foreach (array_reverse($chain) as $ch) {
            // array_merge_recursive appends values with numeric keys
            // instead of replacing the old value, so we need the
            // PHP>5.3 only array_replace_recursive instead
            $c = array_replace_recursive($c, $ch);
        }
        $this->_config = $c;
        $this->_configChain = $chain;
    }


    public function dump($array, $indent = false, $wordwrap = false)
    {
        return $this->_yamlParser->dump($array, $indent, $wordwrap);
    }

    public function configChain()
    {
        return $this->_configChain;
    }


    public function __get($field)
    {
        if (array_key_exists($field, $this->_config)) {
            if (is_array($this->_config[$field])) {
                return new Utils_Config_PropertyWrapper($this->_config[$field]);
            }
            else {
                return self::normalize($this->_config[$field]);
            }
        }
        else {
            return Utils_Config_NullPropertyWrapper::instance();
        }
    }

    public function __isset($field)
    {
        return array_key_exists($field, $this->_config);
    }


    public static function normalize($value)
    {
        if (strtolower($value) === 'false') {
            $value = false;
        }
        elseif (strtolower($value) === 'true') {
            $value = true;
        }
        elseif (is_numeric($value)) {
            $value = intval($value);
        }
        return $value;
    }
}


final class Utils_Config_PropertyWrapper
{
    private $_subArray = null;

    public function __construct($sub)
    {
        $this->_subArray = $sub;
    }

    public function __get($field)
    {
        if (array_key_exists($field, $this->_subArray)) {
            if (is_array($this->_subArray[$field])) {
                return new self($this->_subArray[$field]);
            }
            else {
                return Utils_Config::normalize($this->_subArray[$field]);
            }
        }
        else {
            return Utils_Config_NullPropertyWrapper::instance();
        }
    }

    public function __isset($field)
    {
        return array_key_exists($field, $this->_subArray);
    }

    public function toArray()
    {
        return $this->_subArray;
    }
}

final class Utils_Config_NullPropertyWrapper
{
    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __isset($field) { return false; }
    public function __get($field) { return self::instance(); }
}
