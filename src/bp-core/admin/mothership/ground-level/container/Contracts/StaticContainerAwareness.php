<?php

declare(strict_types=1);

namespace GroundLevel\Container\Contracts;

use GroundLevel\Container\Container;

/**
 * Interface for static container awareness.
 */
interface StaticContainerAwareness
{
    /**
     * Set the container.
     *
     * @param Container $container The container instance.
     */
    public static function setContainer(Container $container): void;

    /**
     * Get the container.
     *
     * @return Container|null The container instance.
     */
    public static function getContainer(): ?Container;
}
