<?php

class ZFE_GeoJson
{
    const TYPE_POINT = "Point";
    const TYPE_MULTIPOINT = "MultiPoint";

    private $type;

    private $coords;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function setCoordinates($latitude, $longitude)
    {
        $this->coords = array(floatval($longitude), floatval($latitude));
    }

    public function toArray()
    {
        return array(
            'type' => $this->type,
            'coordinates' => $this->coords
        );
    }

    public function getJson()
    {
        return json_encode($this->toArray());
    }
}