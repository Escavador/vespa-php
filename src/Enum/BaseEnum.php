<?php

namespace Escavador\Vespa\Enum;

abstract class BaseEnum
{
    protected static $cache = [];

    /**
     * Returns the key and value set of the Enum constants from the value
     *
     * @param string $value
     * @return object
     */
    public static function fromValue($value)
    {
        $name = static::getConstantName($value);
        $value = static::getConstantValue($name);
        return (object)[
            'name' => $name,
            'value' => $value
        ];
    }

    /**
     * Returns the name of an constant Enum from the value of the constant
     *
     * @param string $value Valor de uma constante do Enum
     * @return string|null
     */
    public static function getConstantName(string $value)
    {
        if (!static::isValidValue($value)) {
            return null;
        }

        $b = array_flip(static::keys());
        return ucfirst($b[$value]);
    }

    /**
     * Returns the value of an constant Enum from the name of the constant
     *
     * @param string $key
     * @return string|null
     */
    public static function getConstantValue(string $key)
    {
        if (!static::isValidConstant($key)) {
            return null;
        }

        return ucfirst(static::keys()[$key]);
    }

    public static function toArray()
    {
        $class = static::class;

        if (!isset(static::$cache[$class])) {
            static::$cache[$class] = static::keys();
        }

        return static::$cache[$class];
    }

    /**
     * Checks whether the name of constant is valid for the Enum
     *
     * @param string $name
     * @param bool $strict
     * @return bool
     */
    public static function isValidConstant(string $name, bool $strict = false)
    {
        $constants = static::keys();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    /**
     * Checks whether the value of constant is valid for the Enum
     *
     * @param string $value
     * @param bool $strict
     * @return bool
     */
    public static function isValidValue(string $value, bool $strict = true)
    {
        $values = array_values(static::keys());
        return in_array($value, $values, $strict);
    }

    /**
     * Returns the enum constants
     *
     * @return array
     */
    public static function keys()
    {
        return \array_keys(static::toArray());
    }

    /**
     * Returns the values of the Enum constants
     *
     * @return array
     */
    public static function values()
    {
        $values = array();

        /** @psalm-var T $value */
        foreach (static::toArray() as $key => $value) {
            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Returns the name of the Enum without the namespace (basename)
     *
     * @return string Enum class basename
     */
    public static function enumName()
    {
        return class_basename(static::class);
    }
}
