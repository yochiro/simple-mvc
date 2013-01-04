<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Implements a callable functor with closure-like support for PHP versions < 5.3.
 *
 * Usage :
 *   - <code>$f = new PhpClosure([closure params], callback)</code>
 *     => To be used inside callback parameters: array($f, 'call')
 *   - <code>$f = PhpClosure::get([closure params], callback)</code>
 *     => Can be passed inside callback parameters as is
 *
 * - [closure params] : array of variable names => value
 * These variables will be available inside the defined callback as their defined names (array key)
 * - callback : PHP valid callback, eg. through create_function
 *   Note : the FIRST PARAMETER of the callback IS ALWAYS the PhpClosure instance!
 *   eg. <code>function($v, $w)</code> (valid callback for array_reduce) must be function($c, $v, $w), where $c points to this.
 *
 * Accessing/Setting closure parameters :
 *   The callback has access to all variables previously defined through PhpClosure instance through the proxy instance
 * passed as the first parameter.
 *   Ie. <code>function($c, $v, $w) { if ($v === $c->foo) { $c->bar = $w; } }</code>, where foo and bar were defined as :
 * <code>PhpClosure::get(array('foo' => 1, 'bar' => 2), create_function(...));</code>
 */
final class PhpClosure
{
    /**
     * List of closure variables
     * @var array(string=>mixed)
     */
    private $variables;

    /**
     * Function callback
     * @var mixed
     */
    private $callback;

    /**
     * Creates a new PHP closure using specified closure variables and callback
     *
     * To make this closure callable, the caller must wrap it the same way objects
     * are wrapped to use their class methods as callback : array($this, 'call').
     * call() is the method to call the closure.
     *
     * @param array $variables Array of variables to use in closure : name => val
     * @param $callback callback : callable method. First parameter is always $this
     */
    public function __construct(array $variables, $callback)
    {
        $this->variables = $variables;
        $this->callback = $callback;
    }

    /**
     * Creates a callable closure using specified closure variables and callback
     *
     * Returns an array which can be passed as a parameter where callback types
     * are expected.
     *
     * @param array $variables Array of variables to use in closure : name => val
     * @param $callback callback : callable method. First parameter is always $this
     * @return array : valid callback
     */
    static public function get(array $variables, $callback)
    {
        $closure = new self($variables, $callback);
        return array($closure, 'call');
    }

    /**
     * Returns specified closure variable from current object context
     *
     * @param string $name closure variable name to return
     * @return closure variable value
     */
    public function &__get($name)
    {
        return $this->variables[$name];
    }

    /**
     * Returns whether specified closure variable is defined in current object context
     *
     * @param string $name closure variable name to set
     * @return true|false is specified variable defined ?
     */
    public function __isset($name)
    {
        return (isset($this->variables[$name]));
    }

    /**
     * Sets a value to specified closure variable from current object context
     *
     * @param string $name closure variable name to set
     * @param mixed $value value of variable
     */
    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Calls stored callback
     *
     * First argument is $this.
     * Second argument and after are whatever was passed during the call to this method.
     * eg. <code>$c->call($arg1, $arg2, $arg3)</code> translates as ($this, $arg1, $arg2, $arg3)
     * when actually calling the callback.
     *
     * @param mixed parameters to pass to the callback
     * @return mixed whatever's returned by the callback
     */
    public function call()
    {
        $arguments = func_get_args();
        array_unshift($arguments, $this);
        return call_user_func_array($this->callback, $arguments);
    }
}
?>
