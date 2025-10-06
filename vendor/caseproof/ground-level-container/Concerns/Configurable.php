<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container\Concerns;

use BuddyBossPlatform\GroundLevel\Container\Container;
trait Configurable
{
    /**
     * Returns a key=>value list of default parameters.
     *
     * @return array
     */
    public abstract function getDefaultParameters() : array;
    /**
     * Configures the dependency's parameters.
     *
     * If a default parameter already exists on the container, it will not be overwritten,
     * otherwise it will be added to the container using the default value.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public function configureParameters(Container $container) : void
    {
        foreach ($this->getDefaultParameters() as $key => $defaultVal) {
            if ($container->has($key)) {
                continue;
            }
            $container->addParameter($key, $defaultVal);
        }
    }
}
