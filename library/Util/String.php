<?php

abstract class ZFE_Util_String
{
    /**
     * trim
     *
     * Multibyte-safe trim function
     * Also trims fullwidth spaces
     *
     * @param string $str
     * @return string
     */
    public static function trim($str)
    {
        return mb_ereg_replace("[ 　]*$", "", mb_ereg_replace("^[ 　]*", "", $str));
    }

    /**
     * isMultiByte
     *
     * Checks whether the given string is a multibyte string
     */
    public static function isMultiByte($str)
    {
        return strlen($str) != mb_strlen($str);
    }

    /**
     * chop
     *
     * Multibyte-safe chop function that adds an ellipsis
     * at the end if the string is too long
     *
     * @param string $str
     * @param integer $n Maximum length of the string
     * @param string $ellipsis
     * @return string
     */
    public static function chop($str, $n, $ellipsis = '...')
    {
        $str = self::trim($str);
        if (mb_strlen($str) > $n) {
            $str = self::trim(mb_substr($str, 0, $n));

            // If the exact chop does not end in a sentence-ending punctuation character
            // chop off some more to fit in the ellipsis
            if (mb_strpos('.!?。？！', mb_substr($str, mb_strlen($str) - 1)) === false) {
                $str = self::trim(mb_substr($str, 0, $n - mb_strlen($ellipsis))) . $ellipsis;
            }
        }

        return $str;
    }

    /**
     * chopWords
     *
     * Multibyte-safe functions to chop a string but keep
     * the words intact. Also adds an ellipsis at the end
     * if the string is too long
     *
     * @param string $str
     * @param string $n Maximum length of the string
     * @param string $ellipsis
     * @return string
     */
    public static function chopWords($str, $n, $ellipsis = '...')
    {
        $str = self::trim($str);
        if (mb_strlen($str) <= $n) return $str;

        $words = explode(' ', $str);

        $ret = '';
        while (mb_strlen($ret . ($word = array_shift($words))) <= $n) {
            // If the next token does not end in a sentence-ending punctuation character
            // test the length including ellipsis
            if (mb_strpos('.!?。？！', mb_substr($word, mb_strlen($word) - 1)) === false) {
                if (mb_strlen($ret . $word . $ellipsis) > $n) break;
            }
            $ret .= $word . ' ';
        }
        $ret = trim($ret);

        if (mb_strpos('.!?。？！', mb_substr($ret, mb_strlen($ret) - 1)) === false) {
            $ret .= $ellipsis;
        } 

        return $ret;
    }

    /**
     * A convenience function to check if the string starts
     * with some string.
     */
    public static function startsWith($str, $start)
    {
        $str = @trim($str);

        return strpos($str, $start) === 0;
    }

    /**
     * A convenience function to check if the string ends with
     * some string.
     */
    public static function endsWith($str, $end)
    {
        $str = @trim($str);

        return strrpos($str, $end) === strlen($str) - strlen($end);
    }

    /**
     * An utility function to convert a string to camelcase.
     * The rules are simple. The first character is lowercase.
     * Every underscore, period or dash is a sign that the
     * next character should be capitalized.
     *
     * Optimized version. Reference:
     * http://www.mendoweb.be/blog/php-convert-string-to-camelcase-string/
     */
    public static function toCamelCase($str, $delim = array('-', '_', '.'))
    {
        $ret = '';

        $str = trim(str_replace($delim, ' ', $str));
        $str = ucwords($str);
        $str = lcfirst(str_replace(' ', '', $str));

        return $str;
    }

    public static function fromCamelCase($str, $replacement = '-')
    {
        $ret = '';

        $len = strlen($str);
        for($i = 0; $i < $len; $i++) {
            if ($i && ctype_upper($str[$i])) $ret .= $replacement;

            $ret .= strtolower($str[$i]);
        }

        return $ret;
    }
}
