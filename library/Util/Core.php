<?php

abstract class ZFE_Util_Core
{
    public static function value($val, $default = null)
    {
        return @isset($val) ? $val : $default;
    }
}
