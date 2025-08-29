<?php

declare(strict_types=1);

namespace GroundLevel\Container\Concerns;

use GroundLevel\Container\Container;

/**
 * Trait for static container awareness.
 */
trait HasStaticContainer
{
    /**
     * Container instance.
     *
     * @var Container
     */
    protected static $container = null;

    /**
     * Set the container.
     *
     * @param Container $container The container instance.
     */
    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the container.
     *
     * @return Container|null The container instance.
     */
    public static function getContainer(): ?Container
    {
        return self::$container;
    }
}
