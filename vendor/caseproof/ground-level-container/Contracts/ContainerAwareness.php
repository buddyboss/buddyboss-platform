<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container\Contracts;

use BuddyBossPlatform\GroundLevel\Container\Container;
interface ContainerAwareness
{
    /**
     * Retrieves a container.
     *
     * @return \GroundLevel\Container\Container
     */
    public function getContainer() : Container;
    /**
     * Sets a container.
     *
     * @param  \GroundLevel\Container\Container $container The container.
     * @return \GroundLevel\Container\Contracts\ContainerAwareness
     */
    public function setContainer(Container $container) : ContainerAwareness;
}
