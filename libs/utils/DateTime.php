<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines date-time utilities to convert datetime values
 *
 * @package smvc
 */
final class Utils_DateTime
{
    /**
     * Returns specified datetime string as a UTC timestamp
     *
     * @param string $str the datetime to return as UTC timestamp
     * @return integer UTC timestamp
     */
    public static function toUTCTimestamp($str)
    {
        $out = 0;
        $tz = (Zend_Registry::isRegistered('timezone')?Zend_Registry::get('timezone'):'UTC');
        $oldTz = date_default_timezone_get();
        date_default_timezone_set($tz);
        $out = strtotime($str);
        date_default_timezone_set($oldTz);
        return $out;
    }

    /**
     * Returns specified datetime string as a UTC datetime string
     *
     * Input and output are both string, but the output string will be displayed
     * as UTC timezone regardless of initial string timezone.
     *
     * @param string $str the datetime to return in UTC
     * @param string $format optional output format
     * @return string datetime, in UTC timezone
     */
    public static function toUTCTime($str, $format='Y-m-d H:i:s')
    {
        $str .= ' '.(Zend_Registry::isRegistered('timezone')?Zend_Registry::get('timezone'):'UTC');
        return gmdate($format, strtotime($str));
    }

    /**
     * Returns specified timestamp to a datetime string under configured timezone
     *
     * Timezone is retrieved from Zend_Registry if 'timezone' can be found and is set.
     * Otherwise, UTC is used as a default.
     * Optionally, the output string format can be specified.
     *
     * @param integer $ts the timestamp to convert to timezoned datetime string
     * @param string $format optional datetime string output format
     * @return string the datetime string with specified format and timezone
     */
    public static function toTzTime($ts, $format='Y/m/d H:i:s')
    {
        $out = null;
        $tz = (Zend_Registry::isRegistered('timezone')?Zend_Registry::get('timezone'):'UTC');
        $oldTz = date_default_timezone_get();
        date_default_timezone_set($tz);
        $out = date($format, $ts);
        date_default_timezone_set($oldTz);
        return $out;
    }
}
