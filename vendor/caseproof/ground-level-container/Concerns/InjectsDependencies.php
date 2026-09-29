<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container\Concerns;

use BuddyBossPlatform\GroundLevel\Container\Container;
/**
 * Injects container dependencies into properties on first access.
 *
 * Classes using this trait define an `inject()` method that maps property names
 * to container IDs. Properties are resolved from the container when accessed
 * via PHP's __get() magic method.
 *
 * IMPORTANT: Use class-level @property tags for IDE support, since injected
 * properties must NOT be declared on the class (PHP does not call __get() for
 * declared properties).
 *
 * Works for both services and parameters. The container ID can be a class name
 * or a parameter key.
 */
trait InjectsDependencies
{
    /**
     * Resolves a dependency on property access.
     *
     * @throws \GroundLevel\Container\NotFoundException If the dependency cannot be resolved.
     *
     * @param  string $name The property name.
     * @return mixed
     */
    public function __get($name)
    {
        $map = $this->inject();
        if (isset($map[$name])) {
            return $this->getContainer()->get($map[$name]);
        }
        $parent = \get_parent_class($this);
        if (\false !== $parent && \method_exists($parent, '__get')) {
            return parent::__get($name);
        }
        // Mimic PHP 7.4's native undefined property warning since defining __get()
        // prevents PHP from triggering it. Uses E_USER_WARNING as the closest
        // equivalent to PHP's native E_WARNING for undefined properties.
        \trigger_error('Undefined property: ' . static::class . "::\${$name}", \E_USER_WARNING);
        return null;
    }
    /**
     * Returns a map of property names to container IDs for injection.
     *
     * Override this method to declare dependencies. Keys are property names,
     * values are container IDs (class names for services, parameter keys for params).
     *
     * @return array<string, string>
     */
    protected function inject() : array
    {
        return [];
    }
    /**
     * Returns the container instance for dependency resolution.
     *
     * IMPORTANT: override this method if the container is accessed differently in your plugin.
     *
     * @throws \RuntimeException If called before Bootstrap has been initialized.
     */
    protected function getContainer() : Container
    {
        $instance = \BuddyBossPlatform\GroundLevel\Package\Bootstrap::instance();
        if (null === $instance) {
            throw new \RuntimeException('getContainer() was called before Bootstrap has been initialized. ' . 'Ensure Bootstrap is constructed before accessing injected dependencies.');
        }
        return $instance->container();
    }
}
