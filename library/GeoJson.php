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
        $obj->setCoordinates($arr['coordinates'][1], $arr['coordinates'][0]);

        return $obj;
    }

    public function setCoordinates($latitude, $longitude)
    {
        $this->coords = array(floatval($longitude), floatval($latitude));
    }

    public function getLatitude()
    {
        if (!is_array($this->coords)) return self::$_default[1];

        return $this->coords[1];
    }

    public function getLongitude()
    {
        if (!is_array($this->coords)) return self::$_default[0];

        return $this->coords[0];
    }

    public function toArray()
    {
        return array(
            'type' => $this->type,
            'coordinates' => $this->coords
        );
    }

    /**
     * Only works if it is a point.
     */
    public function toLatLng()
    {
        return implode(",", array_reverse($this->coords));
    }

    public function getJson()
    {
        return json_encode($this->toArray());
    }
}
