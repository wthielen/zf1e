<?php

/**
 * Simple CSS minifier based on Manas Tungare's css-compress.php
 * http://manas.tungare.name/software/css-compression-in-php/
 */
class ZFE_Resources_Minifier_Css extends ZFE_Resources_Minifier_Abstract
{
    public function minify($content)
    {
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        $content = str_replace(': ', ':', $content);
        $content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $content);

        return $content;
    }

}
