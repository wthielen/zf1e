<?php

class ZFE_Model_Mongo_File extends ZFE_Model_Mongo
{
    protected $handle;

    public static function getCollection()
    {
        if (is_null(static::$collection)) {
            throw new ZFE_Model_Mongo_Exception("Please specify the collection name: protected static \$collection");
        }

        return self::getDatabase()->getGridFS(static::$collection);
    }

    public function getHandle()
    {
        return $this->handle;
    }

    public static function map($data)
    {
        $obj = parent::map($data->file);
        $obj->handle = $data;

        return $obj;
    }
}
