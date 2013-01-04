<?php
final class Savant3_Filter_SiteBase extends Savant3_Filter
{
    static function filter($buffer)
    {
        $buffer = preg_replace('!(href|action|src)=(["\'])/([^"\']*)(["\'])!', '$1=$2'.Utils_Url::getSiteBase().'$3$4', $buffer);
        $buffer = preg_replace('!(["\'])/(skin|image|upload|script)/!', '$1'.Utils_Url::getSiteBase().'$2/', $buffer);
        return $buffer;
    }
}
