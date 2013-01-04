<?php
/**
* Returns the URL which loads all needed stylesheets for current page
*/

/**
* Returns the CSS URL for current page
*/
final class Savant3_Plugin_stylesheets extends Savant3_Plugin
{
    public function stylesheets()
    {
        $ss = Utils_ResourceLoader::getStylesheets();
        $url = '/stylesheets';
        return $url.'?'.$this->Savant->escape(http_build_query($ss));
    }
}

