<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

use BuddyBossPlatform\Psr\Container\NotFoundExceptionInterface;
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * Identifier is not defined.
     */
    public const E_UNDEFINED = 100;
    /**
     * Create a new exception instance for the given identifier.
     *
     * @param  string $id The dependency identifier.
     * @return self
     */
    public static function undefinedError(string $id) : self
    {
        return new self("Identifier '{$id}' is not defined.", self::E_UNDEFINED);
    }
}
