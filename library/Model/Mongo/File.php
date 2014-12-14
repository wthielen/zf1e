<?php

/**
 * A flexible Mongo_File model class to model file-based
 * documents.
 *
 * Based on the $storagePath variable, it will either use
 * Mongo's GridFS or the local filesystem pointed at by
 * $storagePath.
 *
 * TODO Needs a check that the data structure has a
 * "filename" entry because the getFile() function depends
 * on it.
 */
class ZFE_Model_Mongo_File extends ZFE_Model_Mongo
{
    const STORAGE_FS = 0;
    const STORAGE_GRIDFS = 1;

    protected $handle = null;
    protected static $storagePath = null;

    public static function getCollection()
    {
        if (is_null(static::$collection)) {
            throw new ZFE_Model_Mongo_Exception("Please specify the collection name: protected static \$collection");
        }

        if (is_null(static::$storagePath)) {
            return self::getDatabase()->getGridFS(static::$collection);
        }

        return parent::getCollection();
    }

    public static function setStoragePath($path)
    {
        static::$storagePath = realpath($path);
    }

    public static function getStorageType()
    {
        return is_null(static::$storagePath) ? static::STORAGE_GRIDFS : static::STORAGE_FS;
    }

    /**
     * Default getFile function
     *
     * Can be overriden on a project-specific basis
     */
    public function getFile()
    {
        $file = implode(DIRECTORY_SEPARATOR, array(
            static::$storagePath,
            $this->filename
        ));

        if (!file_exists($file)) {
            throw new ZFE_Model_Mongo_Exception("File not found: $file");
        }

        return $file;
    }

    public function getBytes()
    {
        if (is_null(static::$storagePath)) {
            return $this->handle->getBytes();
        }

        $file = $this->getFile();

        return file_get_contents($file);
    }

    public static function map($data)
    {
        if (!is_null(static::$storagePath)) {
            return parent::map($data);
        }

        $obj = parent::map($data->file);
        $obj->handle = $data;

        return $obj;
    }
}
