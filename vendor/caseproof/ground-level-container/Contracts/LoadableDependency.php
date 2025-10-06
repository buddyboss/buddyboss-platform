<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container\Contracts;

use BuddyBossPlatform\GroundLevel\Container\Container;
interface LoadableDependency
{
    /**
     * Loads the dependency.
     *
     * This method is called automatically when the dependency is instantiated.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public function load(Container $container) : void;
}
