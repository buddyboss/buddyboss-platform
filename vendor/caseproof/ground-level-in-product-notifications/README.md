Ground Level In-Product Notifications
=====================================

The Ground Level In-Product Notifications package is a service module that provides a notification inbox interface
powered by the [Mothership REST API](https://licenses.caseproof.com/help/api-reference).

---

## Installation

```bash
composer config repositories.caseproof composer https://pkgs.cspf.co
composer require caseproof/ground-level-in-product-notifications
```


## Usage

Within a WordPress plugin, the In-Product Notifications service can be loaded into a container which already has the `GroundLevel\Mothership\Service` service registered.

If the `GroundLevel\Mothership\Service` service is not registered, the In-Product Notifications service will throw a fatal error during initialization.

```php
<?php

use GroundLevel\InProductNotifications\Service as IPNService;

$container = new Container(); // The plugin container with the Mothership service already registered.

// Set Required IPN Service parameters.
$container->addParameter(IPNService::PRODUCT_SLUG, 'product-slug');
$container->addParameter(IPNService::RENDER_HOOK, 'myproduct_admin_header_actions');

// Load the In-Product Notifications service.
$container->addService(
    IPNService::class,
    static function () use ($container): IPNService {
        return new IPNService($container);
    },
    true
);
```

## Customization

### Container Parameters

The inbox can be customized through a series of container parameters and WordPress hooks:

| Parameter | Description | Required | Default |
| --------- | ----------- | -------- | ------- |
| `MENU_SLUG` | The slug of the produt's main WP admin menu item. | No | N/A |
| `PREFIX` | The prefix applied to various strings and IDs used by the service. | No | `grdlvl_` | 
| `PRODUCT_SLUG` | The slug of the product as defined in the Mothership API. | Yes | N/A |
| `RENDER_HOOK` | The name of the hook that will render the inbox. | Yes | N/A |
| `THEME` | A theme configuration object for the inbox. [Configuration documentation](https://github.com/caseproof/ipn-inbox/blob/54a430e0225537878536192a0eece15c79bb88e7/src/contexts/ThemeContext.tsx#L7-L78) | No | N/A |
| `USER_CAPABILITY` | The capability required to view the inbox. | No | `manage_options` |

### WordPress Hooks

#### {$prefix}_root_element_html

Filters the HTML for the root element where the inbox will be rendered.

The dynamic portion of this hook, `{$prefix}`, is the PREFIX parameter
set in the container with the `ipn` suffix appended to it. The default
value is `grdlvl_ipn`.

This filter can be used to wrap the HTML element in another HTML element,
apply CSS classes to the element, and more.

@param string $html The HTML to be rendered.
@param string $id   The HTML ID attribute for the root element.


## Components

The user interface is powered by the [@caseproof/ipn-inbox node packeg](https://github.com/caseproof/ipn-inbox).

The PHP service provides several utilities:

+ The inbox is automatically loaded into the application via the `RENDER_HOOK`.
+ The notification count is added to the main admin menu item when notifications are present
+ A WP CRON event is automatically registered to query for new notifications once daily.
+ A WP CRON event is automatically registered to delete expired and stale (read more than two weeks ago) notifications once daily.
