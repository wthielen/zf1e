<?php

class ZFE_GeoJson
{
    const TYPE_POINT = "Point";
    const TYPE_MULTIPOINT = "MultiPoint";

    private static $_default = array(136, 39);

    private $type;
    private $coords;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public static function create($arr)
    {
        $obj = new static($arr['type']);
        $obj->coords = $arr['coordinates'];

        return $obj;
    }

    public static function createPoint($latitude, $longitude)
    {
        $obj = new static(self::TYPE_POINT);
        $obj->setCoordinates($longitude, $latitude);

        return $obj;
    }

    public function setCoordinates($latitude, $longitude)
    {
        $this->type = self::TYPE_POINT;
        $this->coords = array(floatval($longitude), floatval($latitude));
    }

    public function getCoordinates()
    {
        return array($this->getLatitude(), $this->getLongitude());
    }

    public function getLatitude()
    {
        if (!is_array($this->coords)) return self::$_default[1];

        $coords = $this->type == self::TYPE_POINT ? $this->coords : $this->coords[0];

        return $coords[1];
    }

    public function getLongitude()
    {
        if (!is_array($this->coords)) return self::$_default[0];

        $coords = $this->type == self::TYPE_POINT ? $this->coords : $this->coords[0];

        return $coords[0];
    }

    public function toArray()
    {
        return array(
            'type' => $this->type,
            'coordinates' => $this->coords
        );
    }

    public function toLatLng()
    {
        return $this->getLatitude() . "," . $this->getLongitude();
    }

    public function getJson()
    {
        return json_encode($this->toArray());
    }
}
