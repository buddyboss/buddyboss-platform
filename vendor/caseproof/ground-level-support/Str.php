<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

/**
 * String utilities.
 */
class Str
{
    /**
     * Splits a string to a array of words.
     *
     * Accepts strings in camel, pascal, snake, and kebab case as well as space-separted
     * strings.
     *
     * @param  string $string The string.
     * @return array
     */
    private static function toWords(string $string) : array
    {
        // From Pascal or Snake to space-separated string.
        $string = \preg_replace('/(?<=[a-z])([A-Z]+)/', '_$0', $string);
        // From space-, dash-, or underscore-separated to array.
        return \array_filter(\preg_split('/([-_ ])/', \strtolower($string)));
    }
    /**
     * Converts the input string to camelCase.
     *
     * @link https://wikipedia.org/wiki/CamelCase
     *
     * @param  string $string String to convert.
     * @return string
     */
    public static function toCamelCase(string $string) : string
    {
        return \lcfirst(self::toPascalCase($string));
    }
    /**
     * Converts as string to kebab case
     *
     * @link https://en.wikipedia.org/wiki/Letter_case#Kebab_case
     *
     * @param  string $string String to convert.
     * @return string
     */
    public static function toKebabCase(string $string) : string
    {
        return \implode('-', self::toWords($string));
    }
    /**
     * Converts the input string to PascalCase.
     *
     * @link https://wikipedia.org/wiki/CamelCase
     *
     * @param  string $string String to convert.
     * @return string
     */
    public static function toPascalCase(string $string) : string
    {
        return \array_reduce(self::toWords($string), function ($result, string $part) : string {
            return $result . \ucfirst($part);
        });
    }
    /**
     * Converts the input string to SnakeCase.
     *
     * @link https://wikipedia.org/wiki/Snake_case
     *
     * @param  string $string String to convert.
     * @return string
     */
    public static function toSnakeCase(string $string) : string
    {
        return \implode('_', self::toWords($string));
    }
    /**
     * Converts the input string to space-separated words.
     *
     * Accepts strings in camel, pascal, snake, and kebab case as well as space-separated strings.
     *
     * Note: this method will normalize "weird" capitalization and result in the final
     * string being all in lower case. If you need to preserve capitalization of
     * the input string you should avoid using this method.
     *
     * @param  string $string String to convert.
     * @return string
     */
    public static function toSpacedCase(string $string) : string
    {
        return \implode(' ', self::toWords($string));
    }
    /**
     * Appends a trailing slash to a string without creating double slashes.
     *
     * This function copies the functionality of WordPress's trailingslashit function.
     *
     * @link https://developer.wordpress.org/reference/functions/trailingslashit/
     *
     * @param  string $string The input string.
     * @return string
     */
    public static function trailingslashit(string $string) : string
    {
        return self::untrailingslashit($string) . '/';
    }
    /**
     * Removes trailing forward slashes and backslashes if they exist.
     *
     * This function copies the functionality of WordPress's untrailingslashit function.
     *
     * @link https://developer.wordpress.org/reference/functions/untrailingslashit/
     *
     * @param  string $string The input string.
     * @return string
     */
    public static function untrailingslashit(string $string) : string
    {
        return \rtrim($string, '/\\');
    }
    /**
     * Removes trailing forward underscores if they exist.
     *
     * @param  string $string The input string.
     * @return string
     */
    public static function untrailingUnderscoreIt(string $string) : string
    {
        return \rtrim($string, '_');
    }
}
