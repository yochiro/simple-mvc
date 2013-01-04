<?php
/**
* Returns the URL which loads all needed scripts for current page
*/

/**
* Embeds scripts into the page
*/
final class Savant3_Plugin_scripts extends Savant3_Plugin
{
    public function scripts()
    {
        $out = '';
        $scriptFiles = '<script type="text/javascript" src="/scripts%s"></script>';
        $embeddedScripts = '<script type="text/javascript">//<![CDATA["'.PHP_EOL.'%s'.PHP_EOL.'//]]></script>'.PHP_EOL;
        $allScripts = Utils_ResourceLoader::getScripts();
        // Sort by prio
        krsort($allScripts);
        foreach ($allScripts as $prio => $scripts) {
            $files = array();
            $embedded = array();
            foreach ($scripts as $key => $val) {
                if (is_string($key)) {
                  $files[] = $val;
                } else {
                  $embedded[] = $val;
                }
            }
            if (!empty($files)) {
                $files = implode('&amp;', $files);
                $out .= sprintf($scriptFiles, '?'.$files);
            }
            if (!empty($embedded)) {
                $embedded = implode(PHP_EOL, $embedded);
                $out .= sprintf($embeddedScripts, $embedded);
            }
        }

        echo $out;
    }
}
