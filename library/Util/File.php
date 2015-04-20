<?php

/**
 * Utility class to deal with file operations
 */
final class ZFE_Util_File
{
    /**
     * A recursive rmdir function
     */
    public static function rmdir($path) 
    {
        $ret = true;

        if (file_exists($path) && is_dir($path)) {
            $files = scandir($path);
            foreach($files as $file) {
                if ($file == '.' || $file == '..') continue;

                $full = $path . "/" . $file;
                if (is_dir($full)) {
                    $ret = $ret && self::rmdir($full);
                } else {
                    @unlink($full);
                }
            }

            return $ret && rmdir($path);
        }

        return false;
    }
}
