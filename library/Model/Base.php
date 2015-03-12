<?php

/**
 * A basic model class
 *
 * It knows about translated data members. This is probably best done in
 * a class of its own, but since PHP does not support multiple inheritance
 * until 5.4 (using traits), we keep it here. When 5.4 becomes more
 * mainstream, split the translation capabilities into a separate trait.
 */
class ZFE_Model_Base
{
    protected $_data;
    protected static $translations = array();

    // The language to use for __get and __set
    protected $_lang;

    // Object status
    protected $_status;
    protected static $_defaultStatus = 0;

    const STATUS_CLEAN = 0;
    const STATUS_INITIALIZING = 1;
    const STATUS_DIRTY = 2;
    const STATUS_IMPORT = 3;

    public function __construct()
    {
        $this->_data = array();
        $this->_lang = ZFE_Core::getLanguage();

        $this->_status = static::$_defaultStatus;
    }

    /**
     * The magic setter. It checks for the specific setter function,
     * and whether the given key is a translated entry.
     */
    public function __set($key, $val)
    {
        if ($this->_status == self::STATUS_CLEAN) {
            // Check if a user-defined setter method exists,
            // and use that immediately, returning early
            $setter = "_" . ZFE_Util_String::toCamelCase("set_" . strtolower($key));
            if (method_exists($this, $setter)) {
                $this->$setter($val);
                return;
            }
        }

        // Check the simple case, set it and return immediately
        if (!in_array($key, static::$translations)) {
            $this->_data[$key] = $val;
            return;
        }

        // It is a translated entry. The value needs to be an array,
        // so we initialize it if it does not exist yet
        if (!isset($this->_data[$key])) $this->_data[$key] = array();

        // If the given value is an array, it has to be a language-indexed array
        // of values, so we merge it.
        // Otherwise, we assume the default language's entry is going to be set.
        if (is_array($val)) {
            $this->_data[$key] = array_merge($this->_data[$key], $val);
        } else {
            $this->_data[$key][$this->_lang] = $val;
        }
    }

    /**
     * The magic unsetter. It removes the specific key from the data
     * array.
     */
    public function __unset($key)
    {
        unset($this->_data[$key]);
    }

    /**
     * The magic getter. It checks for the specific getter function,
     * and whether the given key is a translated entry.
     */
    public function __get($key)
    {
        // Check if a user-defined getter method exists,
        // and use that immediately
        $getter = "_" . ZFE_Util_String::toCamelCase("get_" . strtolower($key));
        if (method_exists($this, $getter)) return $this->$getter();

        // Check the simple cases, and return these immediately
        if (!isset($this->_data[$key])) return null;
        if (!in_array($key, static::$translations)) return $this->_data[$key];

        // The key is a translatable data entry, so we try figuring out
        // the language to use
        $lang = $this->_lang;
        if (!isset($this->_data[$key][$lang])) {
            $languages = array_keys($this->_data[$key]);
            $lang = $languages[0];
        }

        return $this->_data[$key][$lang];
    }

    /**
     * The magic isset check. It checks whether the given field
     * has been defined in the data array.
     */
    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }

    /**
     * Returns original values of the requested keys
     * Allows to get computed values if the requested key is a computed
     * value, i.e. there is a _get<key>() function.
     */
    public function toArray($keys = null)
    {
        if (is_null($keys)) return $this->_data;

        $ret = array();
        foreach($keys as $key) {
            $ret[$key] = isset($this->_data[$key]) ? $this->_data[$key] : $this->$key;
        }

        return $ret;
    }

    /**
     * Resets the data member, and sets the fields in such a fashion
     * that it eventually triggers any setter functions, whenever defined
     */
    public function init($data)
    {
        $this->_data = array();

        $this->_status = self::STATUS_INITIALIZING;
        foreach($data as $key => $val) $this->$key = $val;
        $this->_status = self::STATUS_CLEAN;
    }

    /**
     * Just a setting to tell the system not to track changed data
     */
    public static function prepareImport()
    {
        static::$_defaultStatus = self::STATUS_IMPORT;
    }

    /**
     * Sets the value for a given key in a specific language
     *
     * Throws an exception when the given key is not a translated entry.
     */
    public function setTranslation($key, $val, $lang)
    {
        if (!in_array($key, static::$translations)) {
            throw new Exception("Field $key is not a translated entry. Please check " . get_class($this) . "::\$translations.");
        }

        if (!isset($this->_data[$key])) $this->_data[$key] = array();
        $this->_data[$key][$lang] = $val;
    }

    /**
     * Gets the value of the given key in the specified language.
     *
     * If the key is not a translated entry, it will fallback to the normal
     * __get method by returning the key as a data member.
     *
     * It returns null if the key is not specified for the given language.
     */
    public function getTranslation($key, $lang)
    {
        if (!isset($this->_data[$key])) return null;

        if (!in_array($key, static::$translations)) return $this->$key;

        return isset($this->_data[$key][$lang]) ? $this->_data[$key][$lang] : null;
    }

    public function getTranslations($key)
    {
        if (!isset($this->_data[$key])) return null;

        if (!in_array($key, static::$translations)) {
            throw new Exception($key . " is not a translated field.");
        }

        return $this->_data[$key];
    }

    public static function getTranslatedFields()
    {
        return static::$translations;
    }

    public function setLanguage($lang)
    {
        // TODO Check $lang validity?

        $this->_lang = $lang;
    }
}
