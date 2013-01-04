<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Utilities class containing various static methods to
 * provide additional functionalities not available directly in PHP.
 *
 * @package smvc
 */
final class Utils
{
    /**
     * Tests if a string ends with another string
     *
     * @arg string $haystack the string to search in
     * @arg string $needle the string to search for
     * @arg bool $ci true for a case insensitive search
     * @return bool true if needle found at the end of haystack
     */
    public static function str_endsWith($haystack, $needle, $ci=false)
    {
        $nlen = strlen($needle);
        // have to catch this case since substr('', 0) produces an error
        if (0 == $nlen) {
            return true;
        }
        if ($nlen > strlen($haystack)) {
            return false;
        }
        // substr_compare is broken, so...
        if ($ci) {
            if (0 != strcasecmp(substr($haystack, -1 * $nlen), $needle)) {
                return false;
            }
        } else {
            if (0 != strcmp(substr($haystack, -1 * $nlen), $needle)) {
                return false;
            }
        }
        return true;
    }


    public static function sanitize($str)
    {
        //return htmlentities(html_entity_decode($str, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8', false);
        return $str;
    }
}
