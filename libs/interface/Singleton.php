<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines an interface for a singleton-like object
 *
 * It only provides with a mere getInstance method, which supposedly returns
 * an instance of the implementing object, which is assumed to be unique
 * (though it doesn't really matter whether this is actually the case or not).
 * The way the instance is initialized is up to the implementation and may use
 * lazy-evaluation or not.
 *
 * @package smvc
 */
interface Iface_Singleton
{

    /**
     * Returns an instance of the object
     *
     * This object instance is supposedly unique,
     * but there is unfortunately no way to coerce that using interface.
     * However, it would be still be used to check whether a given object has
     * singleton-like access.
     */
    public static function instance();

}
