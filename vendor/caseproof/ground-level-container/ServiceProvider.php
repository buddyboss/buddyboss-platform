<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

use Closure;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
/**
 * Abstract base class for service providers.
 *
 * Service providers declare services and default parameters. Services are either
 * auto-wired by the container via reflection or registered with custom closures.
 *
 * Example:
 *
 *     class ComponentServiceProvider extends ServiceProvider
 *     {
 *         public function services(): array
 *         {
 *             return [
 *                 // Auto-wired singleton (default).
 *                 Foo::class,
 *
 *                 // Auto-wired factory.
 *                 $this->service(Bar::class, self::FACTORY),
 *
 *                 // Custom construction.
 *                 $this->service(Baz::class, self::SINGLETON, function (Container $c) {
 *                     return new Baz($c->get('custom.param'));
 *                 }),
 *             ];
 *         }
 *
 *         public function parameters(): array
 *         {
 *             return [
 *                 'component.prefix' => 'grdlvl_',
 *             ];
 *         }
 *     }
 */
abstract class ServiceProvider
{
    use Hookable;
    /**
     * Lifecycle type: singleton (cached after first resolution).
     */
    public const SINGLETON = 'singleton';
    /**
     * Lifecycle type: factory (new instance each time).
     */
    public const FACTORY = 'factory';
    /**
     * The container instance.
     *
     * @var \GroundLevel\Container\Container
     */
    protected Container $container;
    /**
     * Whether the provider has been booted.
     *
     * @var boolean
     */
    protected bool $booted = \false;
    /**
     * Create a new service provider instance.
     *
     * @param \GroundLevel\Container\Container $container The container instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * Returns an array of provider class names that must be registered before this one.
     *
     * Override this method to declare dependencies on other providers.
     *
     * @return array<class-string<\GroundLevel\Container\ServiceProvider>>
     */
    public function dependencies() : array
    {
        return [];
    }
    /**
     * Returns an array of service definitions.
     *
     * Each entry is either:
     * - A class name string (auto-wired singleton).
     * - A service definition array created via {@see service()}.
     *
     * IMPORTANT: Services are registered in order. Eager services and services
     * with @inject annotations referencing other services must be listed after
     * their dependencies.
     *
     * @return array<int, class-string|array{id: string, lifecycle: string, closure: \Closure|null, eager: bool}>
     */
    public function services() : array
    {
        return [];
    }
    /**
     * Creates a service definition for use in {@see services()}.
     *
     * @param  string        $id        The service identifier (class name or string ID).
     * @param  string        $lifecycle The lifecycle type (self::SINGLETON or self::FACTORY).
     * @param  \Closure|null $closure   A closure for custom construction, or null to auto-wire.
     * @param  boolean       $eager     Whether to instantiate immediately on registration.
     * @return array{id: string, lifecycle: string, closure: \Closure|null, eager: bool}
     */
    protected function service(string $id, string $lifecycle = self::SINGLETON, ?Closure $closure = null, bool $eager = \false) : array
    {
        return ['id' => $id, 'lifecycle' => $lifecycle, 'closure' => $closure, 'eager' => $eager];
    }
    /**
     * Returns a key=>value array of default parameters.
     *
     * Override this method to define default configuration parameters.
     * Parameters will only be registered if they don't already exist,
     * allowing the consuming plugin to override defaults.
     *
     * @return array<string, mixed>
     */
    public function parameters() : array
    {
        return [];
    }
    /**
     * Registers services and parameters with the container.
     *
     * @throws \InvalidArgumentException If a service definition has an invalid lifecycle type.
     */
    public function register() : void
    {
        // 1. Default parameters.
        foreach ($this->parameters() as $key => $defaultValue) {
            if (!$this->container->has($key)) {
                $this->container->parameter($key, $defaultValue);
            }
        }
        // 2. Register services.
        $resolver = $this->container->resolver();
        foreach ($this->services() as $entry) {
            // String shorthand: auto-wired singleton.
            if (\is_string($entry)) {
                $entry = ['id' => $entry, 'lifecycle' => self::SINGLETON, 'closure' => null, 'eager' => \false];
            }
            $id = $entry['id'];
            $lifecycle = $entry['lifecycle'];
            $closure = $entry['closure'] ?? static fn(Container $c) => $resolver->resolve($id);
            $eager = $entry['eager'] ?? \false;
            if (self::SINGLETON === $lifecycle) {
                $this->container->singleton($id, $closure, $eager);
            } elseif (self::FACTORY === $lifecycle) {
                $this->container->factory($id, $closure);
            } else {
                throw new \InvalidArgumentException("Invalid lifecycle type '{$lifecycle}' for service '{$id}'.");
            }
        }
    }
    /**
     * Boot the provider after all providers have been registered.
     */
    public function boot() : void
    {
        if ($this->booted) {
            return;
        }
        $this->addHooks();
        $this->booted = \true;
    }
    /**
     * Returns an array of Hooks that should be added by the class.
     *
     * Override to define WordPress hooks.
     *
     * @return array<\GroundLevel\Support\Models\Hook>
     */
    protected function configureHooks() : array
    {
        return [];
    }
}
