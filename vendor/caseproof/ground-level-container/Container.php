<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

use Closure;
use BuddyBossPlatform\GroundLevel\Container\Contracts\ConfiguresParameters;
use BuddyBossPlatform\GroundLevel\Container\Contracts\ContainerAwareness;
use BuddyBossPlatform\GroundLevel\Container\Contracts\LoadableDependency;
use BuddyBossPlatform\Psr\Container\ContainerInterface;
class Container implements ContainerInterface
{
    /**
     * Registered factory dependencies.
     *
     * @var array<string, \Closure>
     */
    protected $factories = [];
    /**
     * Registered service dependencies.
     *
     * @var array<string, \Closure>
     */
    protected array $services = [];
    /**
     * Registered parameter dependencies.
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];
    /**
     * Instantiated service and parameter dependencies.
     *
     * @var array<string, mixed>
     */
    protected array $instances = [];
    /**
     * Create a new container instance.
     *
     * @param array<string, \Closure> $services   Array of service dependencies.
     * @param array<string, mixed>    $parameters Array of parameter dependencies.
     * @param array<string, \Closure> $factories  Array of factory dependencies.
     */
    public function __construct(array $services = [], array $parameters = [], array $factories = [])
    {
        foreach ($services as $id => $service) {
            $this->addService($id, $service);
        }
        foreach ($parameters as $id => $parameter) {
            $this->addParameter($id, $parameter);
        }
        foreach ($factories as $id => $factory) {
            $this->addFactory($id, $factory);
        }
    }
    /**
     * Adds a service dependency to the container.
     *
     * Services are only instantiated on first use. To have a service reinstantiated
     * it must be readded to the container.
     *
     * @param string   $id            The unique identifier for the service, usually the fully-qualified class name.
     * @param \Closure $createService A closure that returns the service instance.
     * @param boolean  $autoload      Whether to autoload the service on instantiation. If true, the service will be
     *                                retrieved immediately after being added to the container by calling {@see Container::get}.
     *                                This is useful for services that should be loaded immediately instead of lazily.
     */
    public function addService(string $id, Closure $createService, bool $autoload = \false) : void
    {
        unset($this->instances[$id]);
        $this->services[$id] = $createService;
        if ($autoload) {
            $this->get($id);
        }
    }
    /**
     * Adds a factory dependency to the container.
     *
     * Factories are always reinstantiated.
     *
     * @param string   $id            The unique identifier for the factory, usually the fully-qualified class name.
     * @param \Closure $createFactory A closure that returns the factory instance.
     */
    public function addFactory(string $id, Closure $createFactory) : void
    {
        $this->factories[$id] = $createFactory;
    }
    /**
     * Adds a parameter dependency to the container.
     *
     * Parameters are stored as literal values or as a closure that returns a value.
     * If a closure is used, the closure will be called on first use and the result
     * will be stored as a literal value that is returned on subsequent calls.
     *
     * @param string $id    The unique identifier for the parameter.
     * @param mixed  $value The value of the parameter.
     */
    public function addParameter(string $id, $value) : void
    {
        unset($this->instances[$id]);
        $this->parameters[$id] = $value;
    }
    /**
     * Retrieves a dependency from the container.
     *
     * @template T of object
     *
     * @param  string|class-string<T> $id The dependency identifier.
     * @return ($id is class-string<T> ? T : mixed) The dependency. If the dependency is a service or factory,
     *                                               the service or factory instance will be returned. If the
     *                                               dependency is a parameter, the parameter value will be returned.
     *
     * @throws \GroundLevel\Container\NotFoundException If the dependency is not registered with the container.
     */
    public function get(string $id)
    {
        $loc = $this->locate($id);
        $resolver = $this->{$loc}[$id];
        // Stored instances are return immediately.
        if ('instances' === $loc) {
            return $resolver;
        }
        $value = $resolver;
        if ($resolver instanceof Closure) {
            $value = $resolver($this);
            // Set the container if the dependency is container aware.
            if ($value instanceof ContainerAwareness) {
                $value->setContainer($this);
            }
            // Configure the parameters if the dependency is configurable.
            if ($value instanceof ConfiguresParameters) {
                $value->configureParameters($this);
            }
            // Run the load method if the dependency is loadable.
            if ($value instanceof LoadableDependency) {
                $value->load($this);
            }
        }
        if ('factories' !== $loc) {
            $this->instances[$id] = $value;
        }
        return $value;
    }
    /**
     * Determines where to find a dependency within the container.
     *
     * @param  string $id The depedency ID.
     * @return string The location of the dependency.
     * @throws \GroundLevel\Container\NotFoundException If the dependency is not registered with the container.
     */
    protected function locate(string $id) : string
    {
        foreach (['instances', 'services', 'factories', 'parameters'] as $loc) {
            if (\array_key_exists($id, $this->{$loc})) {
                return $loc;
            }
        }
        throw NotFoundException::undefinedError($id);
    }
    /**
     * Determines if a dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    public function has(string $id) : bool
    {
        return $this->hasService($id) || $this->hasFactory($id) || $this->hasParameter($id);
    }
    /**
     * Determines if a factory dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    protected function hasFactory(string $id) : bool
    {
        return \array_key_exists($id, $this->factories);
    }
    /**
     * Determines if a parameter dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    protected function hasParameter(string $id) : bool
    {
        return \array_key_exists($id, $this->parameters);
    }
    /**
     * Determines if a service dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    protected function hasService(string $id) : bool
    {
        return \array_key_exists($id, $this->services);
    }
}
