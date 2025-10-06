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

1. Create a new class that extends `GroundLevel\Mothership\AbstractPluginConnection`.
    - a. Setup the `$this->pluginId`. This will be the Plugin ID/Slug that is used to identify the plugin.
    - b. Setup the `$this->productId`. This is the Product ID/Slug that will be used to connect to the Mothership API.
    - c. Setup the `getLicenseActivationStatus()` method. This will be the method that returns the license activation status.
    - d. Setup the `updateLicenseActivationStatus()` method. This will be the method that updates the license status.
    - e. Setup the `getLicenseKey()` method. This will be the license key that is used to connect to the reseller portal.
    - f. Setup the `updateLicenseKey()` method. This will be the method that updates the license key.
    - g. Setup the `getDomain()` method. This will be the domain that is used to connect to the reseller portal.

```php
<?php

declare(strict_types=1);

use GroundLevel\Mothership\AbstractPluginConnection;

class TestPluginConnection extends AbstractPluginConnection
{

    public function __construct()
    {
        $this->pluginId  = 'memberpress';
        $this->productId = 'memberpress-pro';
    }

    public function getLicenseActivationStatus(): bool
    {
        return get_option('mothership_license_status');
    }

    public function updateLicenseActivationStatus(bool $status) : void
    {
        update_option('mothership_license_status', $status);
    }

    public function getLicenseKey(): string
    {
        return get_option('test_license_key');
    }

    public function updateLicenseKey(string $licenseKey) : void
    {
        update_option('test_license_key', $licenseKey);
    }

    public function getDomain(): string
    {
        return $_SERVER['HTTP_HOST'];
    }
}
```

2. Initialize the `TestPluginConnection` class and set it as the dependency for the `Service` class.

This should allow you to use the API's provided by the Ground Level Mothership package (eg. Request::get(), Products::list(), etc.).

```php
<?php

use GroundLevel\Mothership\Service;


$container = new \GroundLeverl\Container\Container();
$plugin    = new TestPluginConnection();
$container->addService(
    \GroundLevel\Mothership\Service::class,
    static function () use ($container, $plugin): \GroundLevel\Mothership\Service {
        return new \GroundLevel\Mothership\Service($container, $plugin);
    },
    true // Immediately instantiate the service.
);

// Set the static container for the other classes needed by the Mothership service.
Service::setStaticContainersForOtherClasses($container);
```

---

> [!NOTE]
> About Credentials:
>
> Credentials are located in the following priority:
>
> -   A. Environment variables
> -   B. Constants
> -   C. Database (wp options)
>
> This will allow users to store credentials in a more secure manner (env/constants) instead of forcing storage in the wp database (which will be the default).
>
> If a developer wants to just try out the API functionality without using the LicenseManager class
> they can extend to this abstract class and set the `$this->nameForConstantLicenseKey` and `$this->nameForConstantDomain` properties
> with the contants they want to use.
>
> The Request class also supports API Connection using email and api token.

## LicenseManager Usage

The LicenseManager class is used to manage the license for the plugin.

```php
<?php

use GroundLevel\Mothership\Manager\LicenseManager;

// Loads the hooks for the LicenseManager.
$licenseManager = new LicenseManager();

// Display the license activation form if the license is not active and the
// deactivation form if the license is active.
echo $licenseManager->generateLicenseActivationForm();

// Display the license activation form.
echo $licenseManager->generateActivationForm();

// Display the disconnect form.
echo $licenseManager->generateDisconnectForm();
```

There's also a WordPress CRON event that runs every 12 hours to check the license status.

You can hook into the `mothershiptestaddon_license_status_changed` where the `mothershiptestaddon` is the `$this->pluginId` you set
when you extended the AbstractPluginConnection class.

```php
/**
 * @param bool $licenseStatus True if the license is active, false otherwise.
 * @param array $response The response from the license server.
 */
add_action('mothershiptestaddon_license_status_changed', function($licenseStatus, $response) {
    // Do something when the license status is changed.
}, 10, 2);
```

## AddonsManager Usage

The AddonsManager class is used to manage the addons for the plugin.

```php
<?php

use GroundLevel\Mothership\Manager\AddonsManager;

// Loads the hooks for the AddonsManager.
AddonsManager::loadHooks();

// Display the addons.
echo AddonsManager::generateAddonsHtml();

// Fetches the addons data available for the user (via the license key/domain) from Cache or API.
AddonsManager::getAddons(true);
```

## API Request Usage

You can also make API requests to the Mothership API.

### PRODUCTS API

```php
<?php

use GroundLevel\Mothership\Api\Request\Products;

// Get all products.
$args['_embed'] = 'version-latest'; // Optional parameter to include the latest version of the products fetched from the API.
Products::list($args);

// Get a single product.
$productSlug = 'campaignpress-aws';
Products::get($productSlug);

// Get a single product by product slug and version.
$productSlug = 'campaignpress-aws';
$version = '1.0.0';
Products::getVersion($productSlug, $version);
```

### LICENSE ACTIVATIONS API

```php
<?php

use GroundLevel\Mothership\Api\Request\LicenseActivations;

// Activate a license.
$product = 'memberpress'; // The product slug.
$licenseKey = '1234567890';
$domain = 'example.com';
LicenseActivations::activate($product, $licenseKey, $domain);

// Deactivate a license.
LicenseActivations::deactivate($licenseKey, $domain);

// Retrieve a license activation.
LicenseActivations::retrieveLicenseActivation($licenseKey, $domain);

// List all activations for a license.
LicenseActivations::list($licenseKey);
```

### LICENSES API

```php
<?php

use GroundLevel\Mothership\Api\Request\Licenses;

// Create a new license.
$data = [
    'license'      => 'd84c2e4e-f901-40da-ac6e-166bb702a588',
    'user_id'      => 'a519baf3-49f2-453c-a425-fecaa4701c7f',
    'subscription' => 'cspf-0123',
    'is_lifetime'  => true,
];
Licenses::create($data);

//  Get all licenses.
Licenses::list();

// Get a license by license key.
$licenseKey = 'd84c2e4e-f901-40da-ac6e-166bb702a588';
Licenses::get($licenseKey);
```

---

## Connecting to the reseller portal using Email and API Token

You can also connect to the Mothership API using the email and API token strategy.

To connect to the reseller portal using your email and API token, you'll have to:

1. Create a class that extends `GroundLevel\Mothership\AbstractPluginConnection`.
2. Setup the `getEmail()` method. This will be the email that is used to connect to the reseller portal.
3. Setup the `getApiToken()` method. This will be the API token that is used to connect to the reseller portal.

```php
<?php

declare(strict_types=1);

use GroundLevel\Mothership\AbstractPluginConnection;

class TestPluginConnection extends AbstractPluginConnection
{

    public function __construct()
    {
        $this->pluginId  = 'memberpress';
        $this->productId = 'memberpress-pro';

        parent::__construct();
    }

    public function getEmail()
    {
        return 'ronaldo@caseproof.com';
    }

    public function getApiToken()
    {
        return '0dc54d44d5f9ad11ea6f6378754d114983dc5c2671046d14ce3a1e4784656760';
    }
}
```

4. Initialize the `TestPluginConnection` class.

```php
<?php

use GroundLevel\Mothership\Service;

$testPluginConnection = new TestPluginConnection();
new Service($testPluginConnection);
```

5. Use the `Request` class to make API requests.

```php
<?php

use GroundLevel\Mothership\Api\Request;

/**
 * Using Proxy License Key.
 * For example when a customer downloads an add-on via the API from their account page at wishlistmember.com the request will be authenticated with an * Email/API token and the user's license key will be supplied in the X-Proxy-License-Key header.
 */
Service::setProxyLicenseKey('cfe6541f-fb86-4134-8fbb-2202f7d70afc');

// Do API requests that are supported by the Email and API Token connection.
```
