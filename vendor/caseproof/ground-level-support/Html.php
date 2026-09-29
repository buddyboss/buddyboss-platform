<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

class Html
{
    /**
     * Merges CSS class names.
     *
     * Accepts any number of arguments. Each argument can be:
     * - A string of class names (space-separated).
     * - A numerically indexed array of class name strings.
     * - An associative array where keys are class names and values are booleans. Keys with a value of `false` are excluded from the final class list.
     *
     * @param  string|array ...$args Class names or conditional arrays.
     * @return string
     */
    public static function classes(...$args) : string
    {
        $classes = [];
        foreach ($args as $arg) {
            if (\is_string($arg)) {
                $arg = \trim($arg);
                if ('' !== $arg) {
                    $classes[] = $arg;
                }
            } elseif (\is_array($arg)) {
                foreach ($arg as $key => $value) {
                    if (\is_int($key)) {
                        $value = \trim((string) $value);
                        if ('' !== $value) {
                            $classes[] = $value;
                        }
                    } elseif ($value) {
                        $classes[] = $key;
                    }
                }
            }
        }
        return \implode(' ', $classes);
    }
    /**
     * Implodes an associative array of CSS rules into a style attribute value.
     *
     * @param  array $styles The styles array.
     * @return string
     */
    public static function styles(array $styles) : string
    {
        $parts = [];
        foreach ($styles as $prop => $val) {
            $parts[] = "{$prop}:{$val}";
        }
        return \implode(';', $parts);
    }
}
