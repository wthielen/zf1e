<?php

class ZFE_Util_Url
{
    private static $ch = null;

    public static function getHandler()
    {
        if (is_null(self::$ch)) {
            self::$ch = curl_init();
        }

        return self::$ch;
    }

    public static function closeHandler()
    {
        if (!is_null(self::$ch)) {
            curl_close(self::$ch);
            self::$ch = null;
        }
    }

    public static function getJson($url, $params = array())
    {
        $ch = self::getHandler();

        if (count($params)) {
            $url .= "?" . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);

        return $response;
    }
}
