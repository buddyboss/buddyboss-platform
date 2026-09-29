<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

use Closure;
use BuddyBossPlatform\GroundLevel\Container\Exception;
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
     * Registered singleton dependencies.
     *
     * @var array<string, \Closure>
     */
    protected array $singletons = [];
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
     * Registered service providers.
     *
     * @var array<class-string<\GroundLevel\Container\ServiceProvider>, \GroundLevel\Container\ServiceProvider>
     */
    protected array $providers = [];
    /**
     * Providers that have been booted.
     *
     * @var array<class-string, bool>
     */
    protected array $bootedProviders = [];
    /**
     * The resolver instance.
     *
     * @var \GroundLevel\Container\Resolver|null
     */
    private ?Resolver $resolver = null;
    /**
     * Adds a singleton dependency to the container.
     *
     * @param string   $id      The unique identifier for the singleton.
     * @param \Closure $closure A closure that returns the singleton instance.
     * @param boolean  $eager   Whether to resolve immediately (eager loading).
     */
    private function addSingleton(string $id, Closure $closure, bool $eager = \false) : void
    {
        unset($this->instances[$id]);
        $this->singletons[$id] = $closure;
        if ($eager) {
            $this->get($id);
        }
    }
    /**
     * Adds a factory dependency to the container.
     *
     * @param string   $id      The unique identifier for the factory.
     * @param \Closure $closure A closure that returns a new instance.
     */
    private function addFactory(string $id, Closure $closure) : void
    {
        unset($this->instances[$id]);
        $this->factories[$id] = $closure;
    }
    /**
     * Adds a parameter dependency to the container.
     *
     * @param string $id    The unique identifier for the parameter.
     * @param mixed  $value The value of the parameter.
     */
    private function addParameter(string $id, $value) : void
    {
        unset($this->instances[$id]);
        $this->parameters[$id] = $value;
    }
    /**
     * Register a service provider with the container.
     *
     * @template T of \GroundLevel\Container\ServiceProvider
     *
     * @param  \GroundLevel\Container\ServiceProvider|class-string<T> $provider The provider instance or class name.
     * @throws \Exception If the provider class does not extend {@see \GroundLevel\Container\ServiceProvider}.
     */
    private function registerProvider($provider) : void
    {
        $providerClass = \is_string($provider) ? $provider : \get_class($provider);
        // Skip if already registered.
        if (isset($this->providers[$providerClass])) {
            return;
        }
        // Validate that the provider is a subclass of ServiceProvider.
        if (!\is_subclass_of($provider, ServiceProvider::class)) {
            throw new Exception("{$providerClass} must extend \\" . ServiceProvider::class);
        }
        // Resolve provider instance if class name given.
        if (\is_string($provider)) {
            $provider = new $provider($this);
        }
        // Register dependencies first.
        foreach ($provider->dependencies() as $dependency) {
            $this->registerProvider($dependency);
        }
        $provider->register();
        $this->providers[$providerClass] = $provider;
    }
    /**
     * Register a singleton.
     *
     * @template T of object
     *
     * @param  string|class-string<T>                        $id      The singleton identifier.
     * @param  \Closure(\GroundLevel\Container\Container): T $closure A closure that returns the singleton instance.
     * @param  boolean                                       $eager   Whether to resolve immediately (eager loading).
     * @return self
     */
    public function singleton(string $id, Closure $closure, bool $eager = \false) : self
    {
        $this->addSingleton($id, $closure, $eager);
        return $this;
    }
    /**
     * Register multiple singletons.
     *
     * @param  array<string, \Closure> $singletons Map of id => singleton closure.
     * @return self
     */
    public function singletons(array $singletons) : self
    {
        foreach ($singletons as $id => $closure) {
            $this->addSingleton($id, $closure);
        }
        return $this;
    }
    /**
     * Register a factory.
     *
     * Factories return a new instance every time they are retrieved.
     *
     * @template T of object
     *
     * @param  string|class-string<T>                        $id      The factory identifier.
     * @param  \Closure(\GroundLevel\Container\Container): T $closure A closure that returns a new instance.
     * @return self
     */
    public function factory(string $id, Closure $closure) : self
    {
        $this->addFactory($id, $closure);
        return $this;
    }
    /**
     * Register multiple factories.
     *
     * @param  array<string, \Closure> $factories Map of id => factory closure.
     * @return self
     */
    public function factories(array $factories) : self
    {
        foreach ($factories as $id => $closure) {
            $this->addFactory($id, $closure);
        }
        return $this;
    }
    /**
     * Register a parameter.
     *
     * @param  string $key   The parameter key.
     * @param  mixed  $value The parameter value.
     * @return self
     */
    public function parameter(string $key, $value) : self
    {
        $this->addParameter($key, $value);
        return $this;
    }
    /**
     * Register multiple parameters.
     *
     * @param  array<string, mixed> $parameters Map of key => value.
     * @return self
     */
    public function parameters(array $parameters) : self
    {
        foreach ($parameters as $key => $value) {
            $this->addParameter($key, $value);
        }
        return $this;
    }
    /**
     * Register a service provider.
     *
     * Dependencies are resolved automatically via the provider's dependencies() method.
     *
     * @template T of \GroundLevel\Container\ServiceProvider
     *
     * @param  class-string<T> $providerClass The provider class name.
     * @return self
     */
    public function provider(string $providerClass) : self
    {
        $this->registerProvider($providerClass);
        return $this;
    }
    /**
     * Get the resolver instance.
     *
     * @return \GroundLevel\Container\Resolver
     */
    public function resolver() : Resolver
    {
        if (null === $this->resolver) {
            $this->resolver = new Resolver($this);
        }
        return $this->resolver;
    }
    /**
     * Register multiple service providers.
     *
     * Dependencies are resolved automatically via each provider's dependencies() method.
     *
     * @param  array<class-string<ServiceProvider>> $providerClasses Array of provider class names.
     * @return self
     */
    public function providers(array $providerClasses) : self
    {
        foreach ($providerClasses as $providerClass) {
            $this->registerProvider($providerClass);
        }
        return $this;
    }
    /**
     * Retrieves a dependency from the container.
     *
     * @template T of object
     *
     * @param  string|class-string<T> $id The dependency identifier.
     * @return ($id is class-string<T> ? T : mixed) The dependency.
     *
     * @throws \GroundLevel\Container\NotFoundException If the dependency is not registered and cannot be auto-wired.
     */
    public function get(string $id)
    {
        // Factory: always evaluate closure and return result without caching.
        if ($this->hasFactory($id)) {
            return $this->factories[$id]($this);
        }
        // Resolved: return cached instance.
        if ($this->isResolved($id)) {
            return $this->instances[$id];
        }
        // Singleton: evaluate closure, cache the result, and return it.
        if ($this->hasSingleton($id)) {
            $value = $this->singletons[$id]($this);
            $this->instances[$id] = $value;
            return $value;
        }
        // Parameter: evaluate closure if needed, cache the result, and return it.
        if ($this->hasParameter($id)) {
            $value = $this->parameters[$id];
            if ($value instanceof Closure) {
                $value = $value($this);
            }
            $this->instances[$id] = $value;
            return $value;
        }
        // Auto-wireable: resolve, cache the result, and return it.
        if (\class_exists($id)) {
            $value = $this->resolver()->resolve($id);
            $this->instances[$id] = $value;
            return $value;
        }
        throw NotFoundException::undefinedError($id);
    }
    /**
     * Determines if a dependency is explicitly registered with the container.
     *
     * Note: this does NOT cover auto-wireable classes. A class may be resolvable
     * via {@see get()} through auto-wiring even when has() returns false.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    public function has(string $id) : bool
    {
        return $this->hasSingleton($id) || $this->hasFactory($id) || $this->hasParameter($id);
    }
    /**
     * Determines if a dependency has already been resolved and cached.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    public function isResolved(string $id) : bool
    {
        return \array_key_exists($id, $this->instances);
    }
    /**
     * Determines if a factory dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    public function hasFactory(string $id) : bool
    {
        return \array_key_exists($id, $this->factories);
    }
    /**
     * Determines if a parameter dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    public function hasParameter(string $id) : bool
    {
        return \array_key_exists($id, $this->parameters);
    }
    /**
     * Determines if a singleton dependency is registered with the container.
     *
     * @param  string $id The dependency identifier.
     * @return boolean
     */
    public function hasSingleton(string $id) : bool
    {
        return \array_key_exists($id, $this->singletons);
    }
    /**
     * Determines if a provider is registered with the container.
     *
     * @param  string $providerClass The provider class name.
     * @return boolean
     */
    public function hasProvider(string $providerClass) : bool
    {
        return isset($this->providers[$providerClass]);
    }
    /**
     * Get a registered provider by class name.
     *
     * @template T of \GroundLevel\Container\ServiceProvider
     *
     * @param  class-string<T> $providerClass The provider class name.
     * @return T The provider instance.
     *
     * @throws \GroundLevel\Container\NotFoundException If the provider is not registered.
     */
    public function getProvider(string $providerClass) : ServiceProvider
    {
        if (!isset($this->providers[$providerClass])) {
            throw NotFoundException::undefinedError($providerClass);
        }
        return $this->providers[$providerClass];
    }
    /**
     * Boot a service provider.
     *
     * @param \GroundLevel\Container\ServiceProvider $provider The provider to boot.
     */
    public function bootProvider(ServiceProvider $provider) : void
    {
        $providerClass = \get_class($provider);
        if (isset($this->bootedProviders[$providerClass])) {
            return;
        }
        // Boot dependencies first.
        foreach ($provider->dependencies() as $dep) {
            if (isset($this->providers[$dep])) {
                $this->bootProvider($this->providers[$dep]);
            }
        }
        $provider->boot();
        $this->bootedProviders[$providerClass] = \true;
    }
    /**
     * Boot all registered providers.
     *
     * @return self
     */
    public function boot() : self
    {
        foreach ($this->providers as $provider) {
            $this->bootProvider($provider);
        }
        return $this;
    }
}
