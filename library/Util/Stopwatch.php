<?php

final class ZFE_Util_Stopwatch
{
    /**
     * Timings to keep
     */
    private static $timings = array();

    /**
     * start
     *
     * Resets and starts the stopwatch
     *
     * @param string $identifier
     */
    public static function start($identifier)
    {
        if (isset(self::$timings[$identifier])) {
            unset(self::$timings[$identifier]);
        }

        self::$timings[$identifier] = array('start' => microtime(true));
    }

    /**
     * stop
     *
     * Stops the stopwatch
     *
     * @param string $identifier
     */
    public static function stop($identifier)
    {
        if (!isset(self::$timings[$identifier]['start'])) return;

        self::$timings[$identifier]['stop'] = microtime(true);
        self::$timings[$identifier]['duration'] =
            self::$timings[$identifier]['stop'] - 
            self::$timings[$identifier]['start'];
    }

    /**
     * trigger
     *
     * Triggers the stopwatch. First trigger starts, second trigger
     * stops, and third trigger resets.
     *
     * @param string $identifier
     */
    public static function trigger($identifier)
    {
        if (!isset(self::$timings[$identifier])) {
            self::start($identifier);
        } else if (!isset(self::$timings[$identifier]['stop'])) {
            self::stop($identifier);
        } else {
            unset(self::$timings[$identifier]);
        }
    }

    /**
     * get
     *
     * Gets certain timings. Useful for unit testing.
     *
     * @param string $identifier
     * @return Array
     */
    public static function get($identifier)
    {
        if (isset(self::$timings[$identifier]))
            return self::$timings[$identifier];

        return null;
    }

    /**
     * dump
     *
     * Dumps the results on screen
     * @codeCoverageIgnore
     */
    public static function dump()
    {
        // Clears incomplete timings
        self::$timings = array_filter(self::$timings, function($t) {
            return isset($t['duration']);
        });

        // Sort the results on duration, in descending order
        uasort(self::$timings, function($a, $b) {
            if ($a['duration'] == $b['duration']) return 0;

            return ($a['duration'] > $b['duration'] ? -1 : 1);
        });

        echo "<pre style=\"clear: both;\">" . print_r(self::$timings, true) . "</pre>";
    }
}
