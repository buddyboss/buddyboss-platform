<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container\Concerns;

use BuddyBossPlatform\GroundLevel\Container\Container;
trait HasStaticContainer
{
    /**
     * The static container instance.
     *
     * @var \GroundLevel\Container\Container
     */
    protected static Container $container;
    /**
     * Retrieves a container.
     *
     * @return \GroundLevel\Container\Container
     */
    public static function getContainer() : Container
    {
        return static::$container;
    }
    /**
     * Sets a container.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public static function setContainer(Container $container) : void
    {
        static::$container = $container;
    }
}
