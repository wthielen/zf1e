<?php

class ZFE_GeoJson
{
    const TYPE_POINT = "Point";
    const TYPE_MULTIPOINT = "MultiPoint";

    private static $_default = array('lng' => 136, 'lat' => 39);

    private $type;
    private $coords;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public static function create($arr)
    {
        $obj = new static($arr['type']);
        $obj->coords = array_combine(array('lng', 'lat'), array_values($arr['coordinates']));

        return $obj;
    }

    public static function createPoint($latitude, $longitude)
    {
        $obj = new static(self::TYPE_POINT);
        $obj->setCoordinates($latitude, $longitude);

        return $obj;
    }

    public function setCoordinates($latitude, $longitude)
    {
        $this->type = self::TYPE_POINT;
        $this->coords = array(
            'lng' => floatval($longitude),
            'lat' => floatval($latitude)
        );
    }

    public function getCoordinates()
    {
        return array($this->getLatitude(), $this->getLongitude());
    }

    public function getLatitude()
    {
        if (!is_array($this->coords)) return self::$_default['lat'];

        $coords = $this->type == self::TYPE_POINT ? $this->coords : $this->coords[0];

        return $coords['lat'];
    }

    public function getLongitude()
    {
        if (!is_array($this->coords)) return self::$_default['lng'];

        $coords = $this->type == self::TYPE_POINT ? $this->coords : $this->coords[0];

        return $coords['lng'];
    }

    public function toArray()
    {
        return array(
            'type' => $this->type,
            'coordinates' => array_values($this->coords)
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
