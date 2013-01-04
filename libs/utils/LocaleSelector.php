<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Stores locale information and selects the optimal locale
 *
 * @package smvc
 */
final class Utils_LocaleSelector implements Iface_Singleton
{
    /**
     * Singleton instance
     * @var self
     */
    private static $_instance = null;


    /** @var array $supported supported locales normalized => original */
    protected $supported = null;

    /** @var string $requested the requested locale string (optional) */
    protected $requested = null;

    /** @var string $accept the HTTP accept string (optional) */
    protected $accept = null;

    /** @var string $cache the optimal locale if already determined */
    protected $cache = null;


    /**
     * Returns an instance of the object
     *
     * This object instance is supposedly unique,
     * but there is unfortunately no way to coerce that using interface.
     * However, it would be still be used to check whether a given object has
     * singleton-like access.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Normalizes a locale string
     *
     * The normalized form of the locale string uses "-" as a separator as
     * defined in Section 3.10 of RFC 2616, the HTTP/1.1 specification.
     * Note that locale strings are compared using a case-insensitive
     * comparison, which is more efficient than normalizing the case in this
     public * function.
     *
     * @param string $loc the locale string to normalize
     * @return string the normalized string
     */
    protected static function normLocaleString($loc)
    {
        return strtr($loc, '_', '-');
    }

    /**
     * Sets the supported locales
     *
     * The locale strings are normalized, and a map from the normalized
     * strings to the original strings is created.
     *
     * @param array $supported supported locales
     */
    public function setSupported($supported)
    {
        $this->supported = array();
        foreach ($supported as $sup) {
            $this->supported[self::normLocaleString($sup)] = $sup;
        }
        $this->cache = null;
    }

    /**
     * Returns the array of supported locales
     *
     * @return array supported locales
     */
    public function getSupported()
    {
        return array_values($this->supported);
    }

    /**
     * Returns the default locale
     *
     * By convention, the first locale set in the supported locales
     * is considered to be the default locale.
     *
     * @return string the default locale
     */
    public function getDefaultLocale()
    {
        $t = array_values($this->supported);
        return (!(empty($this->supported))?
                array_shift($t):'en_US');
    }

    /**
     * Sets the requested locale string
     *
     * @param string $requested the requested locale string
     */
    public function setRequested($requested)
    {
        $this->requested = $requested;
        $this->cache = null;
    }

    /**
     * Returns the requested locale string
     *
     * @return string the requested locale string
     */
    public function getRequested()
    {
        return $this->requested;
    }

    /**
     * Sets the accept-language string
     *
     * This function exists for unit testing purposes.  It decouples the
     * class from the $_SERVER variables, allowing the class to be easily
     * tested with different accept-language values.
     *
     * The format of this string is defined in Section 14.4 of RFC 2616, the
     * HTTP/1.1 specification.
     *
     * @param string $accept the accept-language string
     */
    public function setAccept($accept)
    {
        $this->accept = $accept;
        $this->cache = null;
    }

    /**
     * Returns the accept-language string
     *
     * If the value is not already specified using setAccept(), it is
     * retreived from $_SERVER['HTTP_ACCEPT_LANGUAGE'].
     *
     * @return string the accept-language string
     */
    public function getAccept()
    {
        if (null == $this->accept) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $this->accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            }
        }
        return $this->accept;
    }

    /**
     * Parses the accept-language string
     *
     * The locales of the accept-language string are returned in an array in
     * priority order.  Note that this function does not take the
     * availability of locales into account.
     *
     * The accept-language string does not necessarily specify the locales in
     * priority order, so a temporary array is used to store the locales
     * according to priority weight.  If two locales have the same quality
     * values, they are sorted in the order that they are specified in the
     * string.  This is implemented using a decrementing counter.
     *
     * Note that the syntax of quality values is specified in Section 3.9 of
     * RFC 2616, the HTTP/1.1 specification.  There is a maximum of three
     * digits after the decimal point.
     *
     * @param string $accept the accept-language string
     * @return array the accept locale strings, prioritized and normalized
     */
    public static function parseAccept($accept)
    {
        $reqs = array();
        if ($accept) {
            $parts = explode(',', $accept);
            $breqs = array();
            $i = 99;
            foreach ($parts as $part) {
                $qidx = strpos($part, ';q=');
                if (false === $qidx) {
                    $loc = $part;
                    $q = 1.0;
                } else {
                    $loc = substr($part, 0, $qidx);
                    $q = floatval(substr($part, $qidx + 3));
                }
                $breqs[floor($q * 100000 + $i)] = $loc;
                $i--;
            }
            krsort($breqs);
            foreach ($breqs as $ckey => $cval) {
                $reqs[] = self::normLocaleString($cval);
            }
        }
        return $reqs;
    }

    /**
     * Finds the optimal locale
     *
     * The requested locale is given top priority if it is specified.  A
     * general match of the requested locale is prioritized over an exact
     * match from accept-language.  So, a match for the requested locale is
     * performed before the accept-language string is even parsed.
     *
     * First a perfect match is searched for.  If one is not found, a second
     * loop searches for a general match.  Note that while it is possible to
     * move the logic of the second loop into the first loop, it is better to
     * keep the loops separate so that the performance of perfect matches is
     * not penalized.
     *
     * If no matches are found, the first locale of the supported locale array
     * is returned as the system default.
     *
     * @return string the optimal locale string
     */
    protected function findBestMatch()
    {
        $sups = array_keys($this->supported);
        if (null != $this->requested) {
            $req = self::normLocaleString($this->requested);
            foreach ($sups as $sup) {
                if (0 == strcasecmp($sup, $req)) {
                    return $this->supported[$sup];
                }
            }
            if (2 < strlen($req)) {
                $req = substr($req, 0, 2);
            }
            foreach ($sups as $sup) {
                if (0 === stripos($sup, $req)) {
                    return $this->supported[$sup];
                }
            }
        }
        $reqs = $this->parseAccept($this->getAccept());
        foreach ($reqs as $req) {
            foreach ($sups as $sup) {
                if (0 == strcasecmp($sup, $req)) {
                    return $this->supported[$sup];
                }
            }
        }
        foreach ($reqs as $req) {
            if (2 < strlen($req)) {
                $req = substr($req, 0, 2);
            }
            foreach ($sups as $sup) {
                if (0 === stripos($sup, $req)) {
                    return $this->supported[$sup];
                }
            }
        }
        return $this->supported[$sups[0]];
    }

    /**
     * Returns the locale string of the optimal locale
     *
     * The locale string is cached, since it is normal to call this method
     * more than once with the same settings.  Note that the cache is cleared
     * whenever any of the determining settings are changed.
     *
     * @return string the optimal locale string
     */
    public function getLocaleString()
    {
        if (null == $this->cache) {
            $this->cache = $this->findBestMatch();
        }
        return $this->cache;
    }

    /**
     * Returns a string representation of the instance
     *
     * This is implemented as a call to getLocaleString() so that the optimal
     * locale string is returned.
     *
     * @return string the optimal locale string
     */
    public function __toString()
    {
        return $this->getLocaleString();
    }

    /**
     * Creates an instance of the class
     *
     * The supported locales can be passed with the constructor or set later
     * with a call to setSupported().
     */
    public function __construct($supported=null)
    {
        if (null != $supported) {
            $this->setSupported($supported);
        }
    }

}
