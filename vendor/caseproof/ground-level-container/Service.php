<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

use BuddyBossPlatform\GroundLevel\Container\Concerns\HasContainer;
use BuddyBossPlatform\GroundLevel\Container\Contracts\ContainerAwareness;
class Service implements ContainerAwareness
{
    use HasContainer;
    /**
     * Service constructor.
     *
     * @param \GroundLevel\Container\Container $container The container instance.
     */
    public function __construct(Container $container)
    {
        $this->setContainer($container);
    }
}
