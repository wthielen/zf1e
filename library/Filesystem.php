<?php

/**
 * Contains methods that relate to PHP filesystem functions (e.g. fopen)
 * The advantage of having them as method calls of an objects is that we can
 * swap this object out during testing so that we're not hitting the filesystem
 *
 * @category   ZFE
 * @package    ZFE_Filesystem
 */
class ZFE_Filesystem
{
    /**
     * fopen - Open a file for reading/writing
     *
     * @param string $filepath Path to the file
     * @param string $mode Open mode eg. a,w
     * @return file pointer resouce
     */
    public function open($filepath, $mode)
    {
        return fopen($filepath, $mode);
    }

    /**
     * feof - Return true if pointer at end of file
     *
     * @param resource $handle File pointer resource obtained from fopen
     * @return boolean
     */
    public function eof($handle)
    {
        return feof($handle);
    }

    /**
     * fclose - Close an opened file
     *
     * @param resource $handle File pointer resource obtained from fopen
     * @return boolean
     */
    public function close($handle)
    {
        return fclose($handle);
    }

    /**
     * fread — Binary-safe file read
     *
     * @param resource $handle File pointer resource obtained from fopen
     * @param int $length Number of bytes to read
     * @return boolean
     */
    public function read($handle, $length)
    {
        return fread($handle);
    }

}
