<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

class Util
{
    /**
     * Retrieves the "base name" of the given class.
     *
     * For example, if the class name is "GroundLevel\Support\Util", this method will return "Util".
     *
     * @param  string|object $class The class name or object.
     * @return string
     */
    public static function classBasename($class) : string
    {
        $class = \is_object($class) ? \get_class($class) : $class;
        $class = \explode('\\', $class);
        return \end($class);
    }
}
