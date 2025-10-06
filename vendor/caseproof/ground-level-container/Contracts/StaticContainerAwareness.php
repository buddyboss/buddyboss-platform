<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container\Contracts;

use BuddyBossPlatform\GroundLevel\Container\Container;
interface StaticContainerAwareness
{
    /**
     * Retrieves a container.
     *
     * @return \GroundLevel\Container\Container
     */
    public static function getContainer() : Container;
    /**
     * Sets a container.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public static function setContainer(Container $container) : void;
}
