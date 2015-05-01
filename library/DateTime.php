<?php

class ZFE_DateTime extends DateTime
{
    public function localeDate($format, $lang = null)
    {
        if (is_null($lang)) $lang = ZFE_Core::getLanguage();

        $dt = new Zend_Date($this->getTimestamp());
        $formats = Zend_Locale_Data::getList($lang, 'date');

        if (isset($formats[$format])) $format = $formats[$format];
        return $dt->toString($format, null, $lang);
    }

    public function localeTime($format, $locale)
    {
        if (is_null($lang)) $lang = ZFE_Core::getLanguage();

        $dt = new Zend_Date($this->getTimestamp());
        $formats = Zend_Locale_Data::getList($lang, 'time');

        if (isset($formats[$format])) $format = $formats[$format];
        return $dt->toString($format, null, $lang);
    }

    public function localeDateTime($format, $locale)
    {
        if (is_null($lang)) $lang = ZFE_Core::getLanguage();

        $dt = new Zend_Date($this->getTimestamp());
        $formats = Zend_Locale_Data::getList($lang, 'datetime');

        if (isset($formats[$format])) $format = $formats[$format];
        return $dt->toString($format, null, $lang);
    }
}
