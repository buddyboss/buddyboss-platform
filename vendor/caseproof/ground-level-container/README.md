Ground Level Container
======================

The Ground Level Container package is a simple dependency injection container
implementing the [PSR-11 Container Interface](https://www.php-fig.org/psr/psr-11/)
intended for usage in WordPress projects built on top of the [Ground Level Framework](https://github.com/caseproof/ground-level-php).

__This package owes a debt for inspiration and implementations via [Pimple](https://github.com/silexphp/Pimple) and [League/Container](https://container.thephpleague.com/).__

---

## Installation

```bash
composer config repositories.ground-level-container vcs https://github.com/caseproof/ground-level-container
composer require caseproof/ground-level-container
```

## Basic Usage

The container provides a fluent API (that can be chained) for registering singletons, factories, parameters, and providers:

```php
use GroundLevel\Container\Container;

$container = (new Container())
    ->singleton(MyConnection::class, static fn() => new MyConnection())
    ->factory(MyFactory::class, static fn() => new MyFactory())
    ->parameters([
        'app.name'  => 'My Plugin',
        'app.debug' => true,
    ])
    ->provider(MyServiceProvider::class)
    ->boot();

// Retrieve dependencies
$service = $container->get(MyService::class);
$param = $container->get('app.debug');
```

### Fluent Methods

| Method | Purpose |
|--------|---------|
| `singleton($id, $closure)` | Register a singleton (cached after first use) |
| `singletons([$id => $closure, ...])` | Register multiple singletons |
| `factory($id, $closure)` | Register a factory (new instance every time) |
| `factories([$id => $closure, ...])` | Register multiple factories |
| `parameter($key, $value)` | Register a single parameter |
| `parameters([$key => $value, ...])` | Register multiple parameters |
| `provider($class)` | Register a service provider |
| `providers([$class, ...])` | Register multiple providers |
| `boot()` | Boot all registered providers |

## Service Providers

Service providers are the recommended way to organize service registration. They provide a clean,
modular approach to registering related services, parameters, and hooks.

### Creating a Service Provider

Extend the abstract `ServiceProvider` class. The container is injected via the constructor:

```php
use GroundLevel\Container\Container;
use GroundLevel\Container\ServiceProvider;

class MyServiceProvider extends ServiceProvider
{
    public const PARAM_API_KEY = 'myservice.api_key';

    /**
     * Returns the service definitions for this provider.
     */
    public function services(): array
    {
        return [
            // Auto-wired singleton (default).
            MyService::class,

            // Auto-wired factory.
            $this->service(MyRepository::class, self::FACTORY),

            // Custom construction.
            $this->service(MyClient::class, self::SINGLETON, static fn(Container $c) => new MyClient(
                $c->get(self::PARAM_API_KEY)
            )),
        ];
    }

    /**
     * Returns provider class names this provider depends on.
     */
    public function dependencies(): array
    {
        return [DatabaseServiceProvider::class];
    }

    /**
     * Returns default parameter values.
     */
    public function parameters(): array
    {
        return [
            self::PARAM_API_KEY => '',
        ];
    }

    /**
     * Returns WordPress hooks to register during boot.
     */
    protected function configureHooks(): array
    {
        return [];
    }
}
```

### Service Definitions

The `services()` method returns an array of service definitions. Each entry is either:

- **A class name string** — shorthand for an auto-wired singleton (the most common case).
- **A `$this->service()` call** — for services that need explicit lifecycle, custom construction, or eager loading.

The `service()` helper accepts:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$id` | `string` | *(required)* | Class name or string ID |
| `$lifecycle` | `string` | `self::SINGLETON` | `self::SINGLETON` or `self::FACTORY` |
| `$closure` | `Closure\|null` | `null` | Custom construction closure, or `null` to auto-wire |
| `$eager` | `bool` | `false` | Whether to resolve immediately on registration |

> **Note:** Services are registered in the order they appear. Eager services and services
> with `@inject` annotations referencing other services must be listed **after** their
> dependencies.

### Registering Providers

Use the fluent `provider()` or `providers()` methods to register providers:

```php
$container = (new Container())
    ->parameters([
        MyServiceProvider::PARAM_API_KEY => 'my-api-key',
    ])
    ->provider(MyServiceProvider::class)
    ->boot();
```

Dependencies are resolved automatically via each provider's `dependencies()` method. You only need to
specify the providers you want to use directly; their dependencies will be registered first.

### Provider Lifecycle

1. **Registration**: `register()` is called, which:
   - Adds default parameters (without overwriting existing ones)
   - Registers all services from `services()` (auto-wired or with custom closures)

2. **Boot**: `boot()` is called after all providers are registered:
   - Adds WordPress hooks from `configureHooks()`
   - Performs any post-registration setup

## Auto-wiring

The container can automatically resolve class dependencies via constructor reflection. When `get()` is called with a class name, the container inspects the constructor and resolves each parameter:

- **Type-hinted classes** are looked up in the container (or auto-wired recursively)
- **Scalar parameters** are matched to container entries via `@inject` annotations on class properties
- **Default values** and **nullable parameters** are used as fallbacks

### Basic Example

Classes with only type-hinted dependencies need no configuration at all:

```php
class Logger
{
    // No dependencies -- resolved directly.
}

class Mailer
{
    public function __construct(Logger $logger)
    {
        // $logger is auto-wired from the container.
    }
}

// Both resolve automatically -- no registration needed.
$mailer = $container->get(Mailer::class);
```

### Scalar Parameters with `@inject`

When a constructor needs scalar values (strings, ints, etc.), annotate the matching property with `@inject` to map it to a container parameter:

```php
class Mailer
{
    /** @inject \App\MailServiceProvider::PARAM_API_KEY */
    protected string $apiKey;

    /** @inject \App\MailServiceProvider::PARAM_FROM */
    protected string $fromAddress;

    public function __construct(Logger $logger, string $apiKey, string $fromAddress)
    {
        // $logger is auto-wired by type hint.
        // $apiKey and $fromAddress are resolved via @inject annotations.
    }
}
```

The `@inject` value can be a class constant reference (resolved at registration time) or a literal container ID:

```php
/** @inject mail.api_key */
protected string $apiKey;
```

> **Note:** On hosts where OPcache runs with `opcache.save_comments=0` (e.g. Kinsta),
> docblocks are stripped from compiled classes. The resolver recovers `@inject` metadata
> by reading each class source file once per request (cached). Files without `@inject`
> incur only a lightweight scan; ensure deployed class sources remain readable on disk.

### Declaring Services in a Provider

Service providers use `services()` to declare their service definitions:

```php
class MailServiceProvider extends ServiceProvider
{
    public const PARAM_API_KEY = 'mail.api_key';
    public const PARAM_FROM    = 'mail.from';

    public function services(): array
    {
        return [
            Logger::class,                                        // auto-wired singleton
            $this->service(MailTransport::class, self::FACTORY),  // auto-wired factory
        ];
    }

    public function parameters(): array
    {
        return [
            self::PARAM_API_KEY => '',
            self::PARAM_FROM   => 'noreply@example.com',
        ];
    }
}
```

Services are auto-wired by default. If a service needs custom construction logic, provide a closure:

```php
public function services(): array
{
    return [
        Logger::class,
        $this->service(MailTransport::class, self::SINGLETON, static fn(Container $c) => new MailTransport(
            $c->get(self::PARAM_API_KEY),
            generateRuntimeToken()
        )),
    ];
}
```

### `InjectsDependencies` Trait

For classes not managed by the container, the `InjectsDependencies` trait provides lazy dependency injection via `__get()`:

```php
/**
 * @property Mailer $mailer
 * @property string $apiKey
 */
class NotificationController
{
    use InjectsDependencies;

    protected function inject(): array
    {
        return [
            'mailer' => Mailer::class,
            'apiKey' => MailServiceProvider::PARAM_API_KEY,
        ];
    }

    public function send(): void
    {
        $this->mailer->send($this->apiKey, ...);
    }
}
```

**Important**: Injected properties must NOT be declared on the class (PHP does not call `__get()` for declared properties). Use `@property` tags on the class docblock for IDE support.
