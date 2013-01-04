<?php
final class Savant3_Filter_Compress extends Savant3_Filter
{
    static function filter($buffer)
    {
        /* remove comments */
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', ' ', $buffer);
        /* remove tabs, spaces, newlines, etc. */
        $buffer = preg_replace('/\s+/', ' ', $buffer);
        return $buffer;
    }
}

