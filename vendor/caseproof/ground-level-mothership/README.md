# Ground Level Mothership

The Ground Level Mothership package is a service module that provides a way for plugins to connect to the [Mothership REST API](https://licenses.caseproof.com/help/api-reference).

---

## Installation

```bash
composer config repositories.caseproof composer https://pkgs.cspf.co
composer require caseproof/ground-level-mothership
```

## Usage

> [!NOTE]
> For a more concrete example of how this can be implemented, please refer to the [Ground Level Sample Plugin](https://github.com/caseproof/ground-level-sample-plugin/) for a real-world usage example.

### 1. Create a PluginConnection class

Create a new class that extends `GroundLevel\Mothership\AbstractPluginConnection` and set the following properties:

- `$this->pluginId` — The Plugin ID/Slug used to identify the plugin.
- `$this->pluginPrefix` — Used to prefix constants for local development credentials.
- `$this->productId` — The Product ID/Slug used to connect to the Mothership API.
- `$this->pluginFile` — The plugin basename (e.g. `'my-plugin/my-plugin.php'`), used for plugin update integration.

```php
<?php

declare(strict_types=1);

use GroundLevel\Mothership\AbstractPluginConnection;

class MyPluginConnection extends AbstractPluginConnection
{
    public function __construct()
    {
        $this->pluginId     = 'my-plugin';
        $this->pluginPrefix = 'MYPLUGIN';
        $this->productId    = 'my-plugin-pro';
        $this->pluginFile   = 'my-plugin/my-plugin.php';
    }
}
```

### Customizing defaults

Override these methods on your `AbstractPluginConnection` subclass when you need custom behavior. Refer to the base class for the default implementation of any method.

**License key storage:**

```php
public function resolveLicenseKey(): string
{
    return (string) get_option('my_custom_license_key', '');
}

public function storeLicenseKey(string $licenseKey): bool
{
    return update_option('my_custom_license_key', $licenseKey);
}
```

> [!NOTE]
> `resolve*()` and `store*()` methods are implementor-facing — they're called by `Credentials` and should not be invoked directly by consumer code. Consumers read and write credentials through `Credentials::getLicenseKey()` and `Credentials::setLicenseKey()` (and equivalents for the other credentials). See [Reading credentials](#reading-credentials) and [Writing credentials](#writing-credentials) below.

**License activation status:**

```php
public function getLicenseActivationStatus(): bool
{
    return (bool) get_option('my_custom_license_active', false);
}

public function setLicenseActivationStatus(bool $status): bool
{
    return update_option('my_custom_license_active', $status);
}
```

**Domain:**

```php
public function resolveDomain(): string
{
    return 'staging.example.com';
}
```

**Prerelease versions.** Return `true` to include alpha, beta, and RC versions in update checks:

```php
public function allowPrereleaseVersions(): bool
{
    return true;
}
```

**Automatic updates.** Controls which updates are applied automatically during WordPress background updates:

```php
public function automaticUpdates(): string
{
    // Available options:
    // self::AUTOMATIC_UPDATE_ALL   - auto-update all versions including major bumps.
    // self::AUTOMATIC_UPDATE_MINOR - auto-update minor and patch only.
    // self::AUTOMATIC_UPDATE_NONE  - never auto-update.
    return self::AUTOMATIC_UPDATE_ALL;
}
```

**Account URL.** Return a URL to the user's account/billing page on your service if you want the LicenseManager UI to link to it:

```php
public function getAccountUrl(): string
{
    return 'https://example.com/account';
}
```

**Email / API token.** Override these to enable the Email + API Token auth strategy instead of License Key + Domain. See [Connecting to the licensing API](#connecting-to-the-licensing-api-using-email-and-api-token) below for the full setup.

```php
public function resolveEmail(): string
{
    return 'me@example.com';
}

public function resolveApiToken(): string
{
    return 'my-api-token';
}
```

### 2. Register the MothershipServiceProvider

Register the `MothershipServiceProvider` with your container. This allows you to use the APIs provided by the Ground Level Mothership package.

```php
<?php

use GroundLevel\Container\Container;
use GroundLevel\Mothership\MothershipServiceProvider;
use GroundLevel\Mothership\AbstractPluginConnection;

$container = (new Container())
    ->singleton(AbstractPluginConnection::class, static fn() => new MyPluginConnection())
    ->provider(MothershipServiceProvider::class)
    ->boot();

// Access the plugin connection instance.
$plugin = $container->get(AbstractPluginConnection::class);
```

---

## Reading credentials

Consumer code reads credentials through the `Credentials` class, **not** by calling the `resolve*()` methods on `AbstractPluginConnection` directly. `Credentials` resolves each value from the following sources, in order:

1. Environment variables
2. Constants
3. The `resolve*()` method on your `AbstractPluginConnection` subclass

The constant/env name is derived from `$this->pluginPrefix` and the credential basename — e.g. a plugin with prefix `MYPLUGIN` looks up `MYPLUGIN_LICENSE_KEY`, `MYPLUGIN_DOMAIN`, etc.

```php
<?php

use GroundLevel\Mothership\Credentials;

$credentials = $container->get(Credentials::class);

$licenseKey = $credentials->getLicenseKey();
$domain     = $credentials->getDomain();
$email      = $credentials->getEmail();
$apiToken   = $credentials->getApiToken();
```

This lets consumers store credentials in env/constants for local or CI environments without requiring DB storage — the `AbstractPluginConnection` resolvers are only consulted if no env/constant value is set.

## Writing credentials

Consumer code writes credentials through the `Credentials` class — not by calling the `store*()` methods on `AbstractPluginConnection` directly. In most flows, `LicenseManager::activateLicense()` and `LicenseManager::deactivateLicense()` handle this for you (deactivation clears the stored key the same way).

```php
$credentials->setLicenseKey($licenseKey);
```

When a credential is supplied via environment variable or constant, the write is silently skipped — subsequent reads resolve to the env/constant value, so persisting it would be redundant.

The setter accepts an optional `$silent` flag. When `false`, an attempted write under an env/constant override throws instead of being skipped silently — useful when a caller wants to surface a misconfiguration rather than absorb it.

```php
$credentials->setLicenseKey($licenseKey, false); // throws if env/const supplies the value
```

## LicenseManager Usage

The LicenseManager class is used to manage the license for the plugin. It's automatically
registered when using the `MothershipServiceProvider`.

```php
<?php

use GroundLevel\Mothership\Manager\LicenseManager;

// Get the LicenseManager from the container
$licenseManager = $container->get(LicenseManager::class);

// Handle form submissions (call early, e.g. in admin_init).
$licenseManager->controller();

// Display the license activation form if the license is not active and the
// deactivation form if the license is active.
echo $licenseManager->generateLicenseForm();

// Explicitly display the license activation form.
echo $licenseManager->generateActivationForm();

// Explicitly display the deactivation form with the active license information.
echo $licenseManager->generateDeactivationForm();
```

### Reading the form submission result

`controller()` renders submission feedback as an admin notice. On pages where admin notices are disabled or removed, a template can render the feedback inline by reading the result back from the manager:

```php
$result = $licenseManager->getLastFormResult();
if (null !== $result && $result->isFailure()) {
    echo '<div class="error">' . esc_html($result->getMessage()) . '</div>';
}

echo $licenseManager->generateLicenseForm();
```

`getLastFormResult()` returns the `Result` of the submission processed in the current request, or `null` if no submission was processed. The value is request-scoped and does not survive a redirect — for the redirect-then-render flow, rely on the flash admin notice instead.

### Programmatic activation and deactivation

```php
// Activate a license. Returns a Result object.
$result = $licenseManager->activateLicense($licenseKey, $domain);

// Activate and install the correct edition if the license entitles a different one.
$result = $licenseManager->activateLicense($licenseKey, $domain, true);

// Deactivate a license.
$result = $licenseManager->deactivateLicense($licenseKey, $domain);
```

### Hooks

There's a WordPress CRON event that runs every 12 hours to check the license status.

The following hooks are available (where `{$pluginId}` is the value of `$this->pluginId`):

```php
/**
 * Fires after a license is successfully activated.
 *
 * @param \GroundLevel\Mothership\Api\Response $activation The API response.
 * @param string                               $licenseKey The license key that was activated.
 * @param string                               $domain     The domain the license was activated for.
 */
add_action('{$pluginId}_license_activated', function ($activation, $licenseKey, $domain) {
    // Do something after activation.
}, 10, 3);

/**
 * Fires after a license is deactivated.
 *
 * @param \GroundLevel\Mothership\Api\Response $response   The API response.
 * @param string                               $licenseKey The license key that was deactivated.
 * @param string                               $domain     The domain the license was deactivated for.
 * @param boolean                              $freed      Whether the activation was freed on the licensing server.
 */
add_action('{$pluginId}_license_deactivated', function ($response, $licenseKey, $domain, $freed) {
    // Do something after deactivation.
}, 10, 4);

/**
 * Fires when a periodic status check detects that the active license has expired.
 *
 * @param \GroundLevel\Mothership\Api\Response $activation The API response.
 */
add_action('{$pluginId}_active_license_expired', function ($activation) {
    // Handle expired license.
}, 10, 1);

/**
 * Fires when a periodic status check detects that the active license is invalid.
 *
 * @param \GroundLevel\Mothership\Api\Response $activation The API response.
 */
add_action('{$pluginId}_active_license_invalidated', function ($activation) {
    // Handle invalidated license.
}, 10, 1);

/**
 * Fires when the installed edition is changed after activation.
 *
 * @param array $editions {
 *     @type object $installed The previously installed edition.
 *     @type object $license   The edition associated with the activated license.
 * }
 */
add_action('{$pluginId}_edition_changed', function ($editions) {
    // Handle edition change.
}, 10, 1);
```

> [!WARNING]
> The `{$pluginId}_license_status_changed` hook is deprecated. Use the specific hooks above instead.

## Plugin Updates

Plugin updates from the Mothership are handled automatically once the connection is configured. The only additional setup required is adding the `Update URI` plugin header to your main plugin file:

```php
/**
 * Plugin Name: My Plugin
 * Update URI: my-plugin
 */
```

By convention, the `Update URI` value matches `AbstractPluginConnection::$pluginId`. This tells WordPress to check the Mothership API for updates instead of WordPress.org.

> [!NOTE]
> Free plugins that omit the `Update URI` header are unaffected and will continue to receive updates from WordPress.org as normal.

> [!NOTE]
> Updates are automatically blocked in development environments when a `.git` directory exists in the plugin folder.

Auto-update behavior is controlled via `AbstractPluginConnection::automaticUpdates()` (see [Customizing defaults](#customizing-defaults) above).

### Add-on Updates

Add-on plugins (and themes) authored for a host plugin self-register their update flow at boot. Each add-on plugin or theme needs:

1. An `Update URI` header matching the add-on's slug.
2. A listener on the host's registration action that calls the registrar.

```php
/**
 * Plugin Name: My Add-on
 * Update URI: my-addon
 */

add_action("{$pluginId}_register_addons", function ($registrar) {
    // Pass the add-on's slug. If its productId differs, pass it as the second argument.
    $registrar->plugin('my-addon');
    // $registrar->plugin('my-addon', 'my-addon-product-id');
});
```

For add-on themes, use the `Update URI` declaration in `style.css` (WordPress 6.1+) and call `$registrar->theme('my-theme')` instead. `theme()` accepts the same optional second argument.

The action name is namespaced with the host plugin's `pluginId`, so add-ons of one host won't trigger update flows for another.

> [!NOTE]
> The `{$pluginId}_register_addons` action fires at `init` with priority 5. Register your listener at plugin load time (the typical pattern) or anywhere before `init`/5 fires. If you must register inside an `init` callback, use priority 4 or lower.

### Plugin Updates Hooks

```php
/**
 * Filters the plugin information displayed in the WordPress plugin details modal.
 *
 * Use this to add extra details (author, homepage, banners, changelog) that
 * are not available from the Mothership API.
 *
 * @param object $pluginInfo The plugin information object.
 * @param object $product    The product data from the Mothership API.
 */
add_filter('{$slug}_plugin_information', function ($pluginInfo, $product) {
    $pluginInfo->author   = '<a href="https://example.com">My Company</a>';
    $pluginInfo->homepage = 'https://example.com/my-plugin';
    $pluginInfo->sections['changelog'] = '<h4>1.0.0</h4><ul><li>Initial release</li></ul>';
    return $pluginInfo;
}, 10, 2);
```

The filter fires for the host plugin and for every registered add-on, namespaced by each one's own slug. So an add-on with slug `my-addon` would hook `'my-addon_plugin_information'` to customize its own modal — independent of the host.

## ActivationTransient

The `ActivationTransient` caches license and activation metadata locally as a WordPress site transient (expires after 24 hours). It's synced automatically when a license is activated and during periodic status checks via `syncActivationTransient()`.

```php
<?php

use GroundLevel\Mothership\Transients\ActivationTransient;

$transient = $container->get(ActivationTransient::class);
$transient->load();

// Access activation data.
$transient->licenseKey;          // string - The license key.
$transient->licenseStatus;       // string - Current license status.
$transient->licenseExpiresAt;    // string - Expiration date (ISO 8601).
$transient->productSlug;         // string - Product slug.
$transient->productName;         // string - Full product name.
$transient->downloadUrl;         // string - Download URL for the licensed product.
$transient->versionNumber;       // string - Latest version number.
$transient->userEmail;           // string - License holder email.

// Activation counts.
$transient->prodActivationsUsed;    // int
$transient->prodActivationsFree;    // int
$transient->prodActivationsAllowed; // int
$transient->testActivationsUsed;    // int
$transient->testActivationsFree;    // int
$transient->testActivationsAllowed; // int
```

## AddonsManager Usage

The AddonsManager class is used to manage the addons for the plugin. It's automatically
registered when using the `MothershipServiceProvider`.

```php
<?php

use GroundLevel\Mothership\Manager\AddonsManager;

// Get the AddonsManager from the container
$addonsManager = $container->get(AddonsManager::class);

// Display the addons.
echo $addonsManager->generateAddonsHtml();

// Fetches the addons data available for the user (via the license key/domain) from Cache or API.
$addonsManager->getAddons(true);
```

## API Request Usage

You can make API requests using the helper classes available from the container.

### PRODUCTS API

```php
<?php

use GroundLevel\Mothership\Api\Request\Products;

// Get the Products service from the container.
$products = $container->get(Products::class);

// Get all products.
$params['_embed'] = 'version-latest'; // Optional parameter to include the latest version of the products fetched from the API.
$products->list($params);

// Get a single product.
$productSlug = 'campaignpress-aws';
$products->get($productSlug);

// Get a single product by product slug and version.
$productSlug = 'campaignpress-aws';
$version = '1.0.0';
$products->getVersion($productSlug, $version);

// Get the latest version for a product.
$products->getVersionLatest($productSlug);
```

### LICENSE ACTIVATIONS API

```php
<?php

use GroundLevel\Mothership\Api\Request\LicenseActivations;

// Get the LicenseActivations service from the container.
$activations = $container->get(LicenseActivations::class);

// Activate a license.
$product = 'memberpress'; // The product slug.
$licenseKey = '1234567890';
$domain = 'example.com';
$activations->activate($product, $licenseKey, $domain);

// Deactivate a license.
$activations->deactivate($licenseKey, $domain);

// Retrieve a license activation.
$activations->retrieveLicenseActivation($licenseKey, $domain);

// Retrieve metadata about a license's activations.
$activations->retrieveLicenseActivationsMeta($licenseKey);

// List all activations for a license.
$activations->list($licenseKey);
```

### LICENSES API

```php
<?php

use GroundLevel\Mothership\Api\Request\Licenses;

// Get the Licenses service from the container.
$licenses = $container->get(Licenses::class);

// Create a new license.
$data = [
    'license'      => 'd84c2e4e-f901-40da-ac6e-166bb702a588',
    'user_id'      => 'a519baf3-49f2-453c-a425-fecaa4701c7f',
    'subscription' => 'cspf-0123',
    'is_lifetime'  => true,
];
$licenses->create($data);

//  Get all licenses.
$licenses->list();

// Get a license by license key.
$licenseKey = 'd84c2e4e-f901-40da-ac6e-166bb702a588';
$licenses->get($licenseKey);

// Add additional activations to a license, optionally passing an external
// reference ID, such as a subscription ID.
$licenses->updateAdditionalActivations($licenseKey, 2, 'sub-12345');

// Remove additional activations from a license.
$licenses->updateAdditionalActivations($licenseKey, -1);
```

### Query parameters

Every resource method accepts a trailing, optional `$params` array that is appended to the request URL as query parameters. On write methods (e.g. `create()`, `update()`), `$params` comes after the request body, so you can embed related resources on a write. For example, requesting the embedded license on user creation:

```php
$users->create($body, ['_embed' => 'license']);
```

### Per-call headers

Both `Request` and the API resource classes (`Products`, `Users`, `Licenses`, etc.) expose fluent, immutable helpers for attaching headers to a single call. Each `with*()` call returns a clone — the original instance (and the container-managed singleton) is never mutated.

```php
// Arbitrary header for one call.
$products->withHeader('X-Foo', 'bar')->getVersions($slug);

// Proxy a request as a specific user's license — used in the Email/Token strategy
// to attribute a request to a user's license (e.g. fingerprinting on file-download
// endpoints).
$products->withProxyLicense($userLicenseKey)->getVersionLatest($slug);

// Chained - each call preserves prior headers on the clone.
$products
    ->withHeader('X-Trace-Id', $traceId)
    ->withProxyLicense($userLicenseKey)
    ->getVersions($slug);

// Works identically on the raw Request instance.
$request->withProxyLicense($userLicenseKey)->get($endpoint);
```

---

## Connecting to the licensing API using Email and API Token

You can also connect to the Mothership API using the email and API token strategy.

To connect to the licensing API using your email and API token, you'll have to:

1. Create a class that extends `GroundLevel\Mothership\AbstractPluginConnection`.
2. Override `resolveEmail()` to return the email used to connect to the licensing API.
3. Override `resolveApiToken()` to return the API token used to connect to the licensing API.

```php
<?php

declare(strict_types=1);

use GroundLevel\Mothership\AbstractPluginConnection;

class TestPluginConnection extends AbstractPluginConnection
{

    public function __construct()
    {
        $this->pluginId     = 'memberpress';
        $this->pluginPrefix = 'MEPR';
        $this->productId    = 'memberpress-pro';
    }

    public function resolveEmail(): string
    {
        return 'ronaldo@caseproof.com';
    }

    public function resolveApiToken(): string
    {
        return '0dc54d44d5f9ad11ea6f6378754d114983dc5c2671046d14ce3a1e4784656760';
    }
}
```

4. Register the `MothershipServiceProvider` with your container.

```php
<?php

use GroundLevel\Container\Container;
use GroundLevel\Mothership\MothershipServiceProvider;
use GroundLevel\Mothership\AbstractPluginConnection;

$container = (new Container())
    ->singleton(AbstractPluginConnection::class, static fn() => new TestPluginConnection())
    ->provider(MothershipServiceProvider::class)
    ->boot();
```

5. Use the `RequestFactory` to make API requests.

```php
<?php

use GroundLevel\Mothership\Api\RequestFactory;

// Get the RequestFactory from the container
$requestFactory = $container->get(RequestFactory::class);

// Create requests using the factory
$request = $requestFactory->create();
```
