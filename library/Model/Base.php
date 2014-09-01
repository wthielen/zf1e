<?php

class ZFE_Model_Base
{
    protected $_data;
    protected static $translations = array();

    public function __construct()
    {
        $this->_data = array();
    }

    /**
     * The magic setter. It checks for the specific setter function,
     * and whether the given key is a translated entry.
     */
    public function __set($key, $val)
    {
        // Check if a user-defined setter method exists,
        // and use that immediately, returning early
        $setter = "_set" . ucfirst(strtolower($key));
        if (method_exists($this, $setter)) {
            $this->$setter($val);
            return;
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
            $lang = ZFE_Util_Core::getLanguage();
            $this->_data[$key][$lang] = $val;
        }
    }

    /**
     * The magic getter. It checks for the specific getter function,
     * and whether the given key is a translated entry.
     */
    public function __get($key)
    {
        // Check if a user-defined getter method exists,
        // and use that immediately
        $getter = "_get" . ucfirst(strtolower($key));
        if (method_exists($this, $getter)) return $this->$getter();

        // Check the simple cases, and return these immediately
        if (!isset($this->_data[$key])) return null;
        if (!in_array($key, static::$translations)) return $this->_data[$key];

        // The key is a translatable data entry, so we try figuring out
        // the language to use
        $lang = ZFE_Util_Core::getLanguage();
        if (!isset($this->_data[$key][$lang])) {
            $languages = array_keys($this->_data[$key]);
            $lang = $languages[0];
        }

        return $this->_data[$key][$lang];
    }

    public function toArray()
    {
        return $this->_data;
    }

    /**
     * Resets the data member, and sets the fields in such a fashion
     * that it eventually triggers any setter functions, whenever defined
     */
    public function init($data)
    {
        $this->_data = array();

        foreach($data as $key => $val) $this->$key = $val;
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

        return isset($this->_data[$key][$lang] ? $this->_data[$key][$lang] : null;
    }
}
