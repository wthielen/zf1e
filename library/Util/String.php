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
}
