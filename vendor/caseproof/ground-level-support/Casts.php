<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

/**
 * Data formats (casts).
 *
 * @method static Casts ARRAY()
 * @method static Casts BOOLEAN()
 * @method static Casts BOOL()
 * @method static Casts DATE_MYSQL()
 * @method static Casts FLOAT()
 * @method static Casts DOUBLE()
 * @method static Casts INTEGER()
 * @method static Casts INT()
 * @method static Casts STRING()
 * @method static Casts OBJECT()
 * @method static Casts OBJ()
 * @method static Casts TIMESTAMP()
 */
class Casts extends Enum
{
    /**
     * Array format.
     */
    public const ARRAY = 'array';
    /**
     * Boolean format.
     */
    public const BOOLEAN = 'bool';
    /**
     * Boolean format (alias).
     */
    public const BOOL = 'bool';
    /**
     * MySQL date time format (Y-m-d H:i:s).
     */
    public const DATE_MYSQL = 'date_mysql';
    /**
     * Float format.
     */
    public const FLOAT = 'float';
    /**
     * Float format (alias).
     */
    public const DOUBLE = 'float';
    /**
     * Integer format.
     */
    public const INTEGER = 'int';
    /**
     * Integer format (alias).
     */
    public const INT = 'int';
    /**
     * String format.
     */
    public const STRING = 'string';
    /**
     * Object format.
     */
    public const OBJECT = 'obj';
    /**
     * Object format (alias).
     */
    public const OBJ = 'obj';
    /**
     * Unix timestamp format.
     */
    public const TIMESTAMP = 'timestamp';
    /**
     * Casts the value to the specified type.
     *
     * @param  Cast|string $cast  The type to cast the value to, see Casts::* constants.
     * @param  mixed       $value The value to cast.
     * @return mixed The casted value.
     * @throws UnexpectedValueException Throws an error when the specified cast isn't defined.
     */
    public static function cast($cast, $value)
    {
        $cast = (new static($cast))->getValue();
        switch ($cast) {
            case self::ARRAY:
                $value = (array) $value;
                break;
            case self::BOOLEAN:
                $value = (bool) $value;
                break;
            case self::DATE_MYSQL:
                $value = \date(Time::FORMAT_MYSQL, Time::toTimestamp($value));
                break;
            case self::FLOAT:
                $value = (float) $value;
                break;
            case self::INTEGER:
                $value = (int) $value;
                break;
            case self::STRING:
                $value = (string) $value;
                break;
            case self::TIMESTAMP:
                $value = Time::toTimestamp($value);
                break;
            case self::OBJECT:
                $value = (object) $value;
                break;
        }
        return $value;
    }
    /**
     * Retrieves the default cast value.
     *
     * @return static
     */
    public static function default()
    {
        return static::STRING();
    }
}
