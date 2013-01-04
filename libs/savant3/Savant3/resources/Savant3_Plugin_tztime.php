<?php
/**
* Returns the specified time using currently defined timezone in the configuration
*/
final class Savant3_Plugin_tztime extends Savant3_Plugin
{
    public function tztime($ts, $format='Y/m/d H:i:s')
    {
        return Utils_DateTime::toTzTime($ts, $format);
    }
}
