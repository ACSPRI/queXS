<?php
final class SettingsStorage extends ArrayObject
{
    protected static $_instance = null;

    public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }

    public static function getInstance()
    {
        if( self::$_instance === NULL ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function get($index)
    {
        $instance = self::getInstance();

        if (!$instance->offsetExists($index)) {
            throw new Exception("No entry is registered for key '$index'");
        }

        return $instance->offsetGet($index);
    }

    public static function set($index, $value)
    {
        $instance = self::getInstance();
        $instance->offsetSet($index, $value);
    }

    public static function isRegistered($index)
    {
        if (self::$_instance === null) {
            return false;
        }
        return self::$_instance->offsetExists($index);
    }

    /**
     * Workaround for http://bugs.php.net/bug.php?id=40442 (ZF-960).
     */
    public function offsetExists($index)
    {
        if (is_array($this)) {
            return array_key_exists($index, $this);
        } else if (is_object($this)) {
            return property_exists('SettingsStorage', $index);
        } else return false;
    }


}
?>
