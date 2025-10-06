<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

use BadMethodCallException;
use JsonSerializable;
use ReflectionClass;
use UnexpectedValueException;
/**
 * A simple enum implementation.
 *
 * All constants defined on a class extending this abstract will be considered
 * enums.
 *
 * The enum value can be retrieved by calling the static method with the
 * same name as the constant, for example: `MyEnum::FOO()` and then calling the
 * method `getValue()` on the returned instance.
 *
 * A default value can be obtained by calling the static method `default()`, the
 * default value will always be the first enum constant defined on the class. To
 * change this behavior, redefine the `default()` method on the extending class
 * to return a different value.
 *
 * This implementation was heavily inspired by myclabs/php-enum
 *
 * @link https://github.com/myclabs/php-enum
 */
abstract class Enum implements JsonSerializable
{
    /**
     * Stores existing constants.
     *
     * @var array
     */
    protected static array $cache = [];
    /**
     * Enum key.
     *
     * @var string
     */
    protected string $key;
    /**
     * Enum value.
     *
     * @var mixed
     */
    protected $value;
    /**
     * Constructor.
     *
     * @param  mixed $value The enum value.
     * @throws UnexpectedValueException If the value is not a valid enum case.
     */
    public function __construct($value)
    {
        if ($value instanceof static) {
            $value = $value->getValue();
        }
        $key = static::search($value);
        if (\false === $key) {
            throw new UnexpectedValueException("Value '{$value}' is not a valid enum case for class " . static::class);
        }
        $this->key = $key;
        $this->value = $value;
    }
    /**
     * Magic static method caller.
     *
     * Allows using the enum cases as static methods, for example for an enum case
     * named `FOO`, the static method `FOO()` will return a new instance of the
     * enum with the value of the `FOO` case.
     *
     * @param  string $name      Method name.
     * @param  array  $arguments Arguments passed to the method.
     * @throws BadMethodCallException If the method or enum case does not exist.
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (!static::isValidKey($name)) {
            throw new BadMethodCallException("No static method or enum constant '{$name}' in class " . static::class);
        }
        return new static(static::cases()[$name]);
    }
    /**
     * Converts the enum to a string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->getValue();
    }
    /**
     * Lists all enum cases.
     *
     * @return array
     */
    public static function cases() : array
    {
        $class = static::class;
        if (empty(static::$cache[$class])) {
            $reflection = new ReflectionClass(static::class);
            static::$cache[$class] = $reflection->getConstants();
        }
        return static::$cache[$class];
    }
    /**
     * Retrieves the default enum case value.
     *
     * Automatically returns the first case value. Extending classes may override
     * this method to return a different default value.
     *
     * @return static
     */
    public static function default()
    {
        return static::values()[0];
    }
    /**
     * Retrieves the default enum case key.
     *
     * This method is final to prevent accidental overrides that could break
     * the synchronization between {@see Enum::default} and {@see Enum::defaultKey}.
     *
     * @return string
     */
    public static final function defaultKey() : string
    {
        return static::search(static::default());
    }
    /**
     * Tests if the enum value is equal to the given value.
     *
     * By default, performs a strict comparison which expects that the given value
     * is an instance of the same enum class and has the same value and type.
     *
     * When $strict is set to false, the comparison is made using the enum value
     * only, allowing comparison of values of different Enums. Note that this still
     * requires the supplied value's type to match the enum value's type. If a
     * loose comparison is required, you can compare the enum value directly, for
     * example: `MyEnum::FOO()->getValue() == $value`.
     *
     * @param  mixed   $value  The enum or enum value to compare.
     * @param  boolean $strict If true, requires the supplied value to be an instance
     *                        of the same enum class and have the same value and type.
     * @return boolean
     */
    public final function equals($value, bool $strict = \true) : bool
    {
        $isEnum = $value instanceof static;
        $expectedValue = $this->getValue();
        if ($strict || $isEnum) {
            return $isEnum && $value->getValue() === $expectedValue;
        }
        return $value === $expectedValue;
    }
    /**
     * Retrieves the enum key.
     *
     * @return string
     */
    public function getKey() : string
    {
        return $this->key;
    }
    /**
     * Retrieves the enum value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Determines if the value is a valid enum case.
     *
     * @param  mixed $value The enum value.
     * @return boolean
     */
    public static function isValid($value) : bool
    {
        return \in_array($value, static::cases(), \true);
    }
    /**
     * Determines if the key is a valid enum case.
     *
     * @param  string $key The enum key.
     * @return boolean
     */
    public static function isValidKey(string $key) : bool
    {
        return \array_key_exists($key, static::cases());
    }
    /**
     * Serializes the enum value for JSON encoding.
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->getValue();
    }
    /**
     * Lists all enum keys.
     *
     * @return string[]
     */
    public static function keys() : array
    {
        return \array_keys(static::cases());
    }
    /**
     * Tests if the enum value is equal to one of the given values.
     *
     * By default, performs a strict comparison which expects that the given value
     * is an instance of the same enum class and has the same value and type.
     *
     * When $strict is set to false, the comparison is made using the enum value
     * only, allowing comparison of values of different Enums. Note that this still
     * requires the supplied value's type to match the enum value's type. If a
     * loose comparison is required, you can compare the enum value directly, for
     * example: `MyEnum::FOO()->getValue() == $value`.
     *
     * @param  array   $values An array of enum values or enums to compare.
     * @param  boolean $strict If true, requires the supplied value to be an instance
     *                        of the same enum class and have the same value and type.
     * @return boolean
     */
    public final function oneOf(array $values, bool $strict = \true) : bool
    {
        foreach ($values as $value) {
            if ($this->equals($value, $strict)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Returns the key of the given value.
     *
     * @param  mixed $value The enum value to search for.
     * @return string|false The key on success, false on failure.
     */
    public static function search($value)
    {
        $value = $value instanceof static ? $value->getValue() : $value;
        return \array_search($value, static::cases(), \true);
    }
    /**
     * Returns all enum values.
     *
     * @return mixed[]
     */
    public static function values() : array
    {
        $values = [];
        foreach (static::cases() as $key => $value) {
            $values[] = new static($value);
        }
        return $values;
    }
}
