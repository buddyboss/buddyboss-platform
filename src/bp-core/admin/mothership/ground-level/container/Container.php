<?php

declare(strict_types=1);

namespace GroundLevel\Container;

/**
 * Simple dependency injection container.
 */
class Container
{
    /**
     * Services stored in the container.
     *
     * @var array
     */
    private array $services = [];

    /**
     * Add a service to the container.
     *
     * @param string   $id       The service ID.
     * @param callable $factory  The factory function.
     * @param bool     $singleton Whether this is a singleton service.
     */
    public function addService(string $id, callable $factory, bool $singleton = false): void
    {
        $this->services[$id] = [
            'factory' => $factory,
            'singleton' => $singleton,
            'instance' => null,
        ];
    }

    /**
     * Get a service from the container.
     *
     * @param string $id The service ID.
     * @return mixed The service instance.
     * @throws \Exception If service not found.
     */
    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            throw new \Exception("Service '$id' not found in container.");
        }

        $service = $this->services[$id];

        if ($service['singleton'] && $service['instance'] !== null) {
            return $service['instance'];
        }

        $instance = $service['factory']();

        if ($service['singleton']) {
            $this->services[$id]['instance'] = $instance;
        }

        return $instance;
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $id The service ID.
     * @return bool True if service exists, false otherwise.
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
