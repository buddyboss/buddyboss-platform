# Add-on Plugin DRM Integration Guide

## Overview

This guide shows how to integrate paid BuddyBoss add-on plugins (Platform Pro, Sharing, etc.) with the centralized DRM system in BuddyBoss Platform.

The DRM system provides:
- ✅ License validation through Mothership
- ✅ Progressive warnings (LOW → MEDIUM → LOCKED)
- ✅ Automatic notifications (email + in-plugin)
- ✅ Feature lockout capabilities
- ✅ Staging server detection
- ✅ Consistent user experience across all add-ons

---

## Quick Start

### Step 1: Register Your Add-on with DRM

In your add-on's main initialization file, register with the DRM Registry:

```php
// File: buddyboss-platform-pro/class-bb-platform-pro.php
// In the __construct() or init() method:

if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
    \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::register_addon(
        'buddyboss-platform-pro',  // Mothership product slug
        'BuddyBoss Platform Pro',   // Display name
        array(
            'version' => $this->version,
            'file'    => BB_PLATFORM_PRO_PLUGIN_FILE,
        )
    );
}
```

### Step 2: Check License Before Loading Features

Before initializing your add-on's features, check if they should be locked:

```php
// Check if features should be locked
if ( \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' ) ) {
    // Don't load features
    add_action( 'admin_notices', array( $this, 'show_lockout_notice' ) );
    return;
}

// Features are allowed - continue initialization
$this->load_integrations();
$this->load_features();
```

### Step 3: Replace Existing License Checks

Replace your existing license validation with the centralized DRM:

**BEFORE:**
```php
function bbp_pro_is_license_valid() {
    if ( bb_pro_check_staging_server() ) {
        return true;
    }

    $license_exists = false;
    if ( class_exists( '\\BuddyBoss\\Core\\Admin\\Mothership\\BB_Plugin_Connector' ) ) {
        $connector = new \\BuddyBoss\\Core\\Admin\\Mothership\\BB_Plugin_Connector();
        $license_status = $connector->getLicenseActivationStatus();
        if ( ! empty( $license_status ) && \\BuddyBoss\\Core\\Admin\\Mothership\\BB_Addons_Manager::checkProductBySlug( 'buddyboss-platform-pro' ) ) {
            $license_exists = true;
        }
    }
    return $license_exists;
}
```

**AFTER:**
```php
function bbp_pro_is_license_valid() {
    // Simply check if features are NOT locked
    return ! \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' );
}
```

---

## Complete Integration Examples

### Example 1: BuddyBoss Platform Pro

**File:** `buddyboss-platform-pro/class-bb-platform-pro.php`

```php
<?php
final class BB_Platform_Pro {

    public function __construct() {
        $this->constants();
        $this->setup_globals();
        $this->load_plugin_textdomain();

        // Register with DRM system EARLY
        $this->register_drm();

        // Check license before loading features
        if ( $this->should_load_features() ) {
            $this->includes();
            $this->setup_actions();
        } else {
            $this->setup_lockout_hooks();
        }
    }

    /**
     * Register with DRM system.
     */
    private function register_drm() {
        if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
            return;
        }

        \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::register_addon(
            'buddyboss-platform-pro',
            'BuddyBoss Platform Pro',
            array(
                'version' => $this->version,
                'file'    => BB_PLATFORM_PRO_PLUGIN_FILE,
                'edition' => defined( 'PRO_EDITION' ) ? PRO_EDITION : 'pro',
            )
        );
    }

    /**
     * Check if features should load.
     */
    private function should_load_features() {
        if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
            // DRM not available - fall back to old method
            return $this->legacy_license_check();
        }

        // Use centralized DRM check
        return ! \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' );
    }

    /**
     * Setup hooks when features are locked.
     */
    private function setup_lockout_hooks() {
        add_action( 'admin_notices', array( $this, 'display_lockout_notice' ) );
        add_action( 'network_admin_notices', array( $this, 'display_lockout_notice' ) );
    }

    /**
     * Display lockout notice in admin.
     */
    public function display_lockout_notice() {
        if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
            return;
        }

        \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::display_lockout_notice(
            'buddyboss-platform-pro',
            'admin_notice'
        );
    }

    /**
     * Legacy license check (fallback if DRM not available).
     */
    private function legacy_license_check() {
        // Your existing license validation code
        return bbp_pro_is_license_valid();
    }
}
```

### Example 2: BuddyBoss Sharing

**File:** `buddyboss-sharing/buddyboss-sharing.php`

```php
<?php
final class BuddyBoss_Sharing {

    public function init() {
        // Register with DRM
        $this->register_drm();

        // Check if features should be enabled
        if ( ! $this->can_load_features() ) {
            add_action( 'admin_notices', array( $this, 'show_license_notice' ) );
            return; // Stop initialization
        }

        // Load features normally
        $this->load_core();
        $this->load_admin();
        $this->load_frontend();
    }

    /**
     * Register with centralized DRM.
     */
    private function register_drm() {
        if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
            \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::register_addon(
                'buddyboss-sharing',
                'BuddyBoss Sharing',
                array(
                    'version' => BUDDYBOSS_SHARING_VERSION,
                    'file'    => BUDDYBOSS_SHARING_PLUGIN_FILE,
                )
            );
        }
    }

    /**
     * Check if features can load.
     */
    private function can_load_features() {
        // Use centralized DRM if available
        if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
            return ! \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-sharing' );
        }

        // Fallback to local license manager
        return \\BuddyBoss\\Sharing\\Core\\License_Manager::instance()->is_valid();
    }

    /**
     * Show license notice.
     */
    public function show_license_notice() {
        if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
            \\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry::display_lockout_notice(
                'buddyboss-sharing',
                'admin_notice'
            );
        }
    }
}
```

---

## API Reference

### BB_DRM_Registry Methods

#### `register_addon( $product_slug, $plugin_name, $args )`
Register an add-on with the DRM system.

**Parameters:**
- `$product_slug` (string) - Mothership product slug (e.g., 'buddyboss-platform-pro')
- `$plugin_name` (string) - Display name for the plugin
- `$args` (array) - Optional. Additional data (version, file, etc.)

**Returns:** `BB_DRM_Addon` instance or `false` on error

**Example:**
```php
$drm = BB_DRM_Registry::register_addon(
    'buddyboss-gamification',
    'BuddyBoss Gamification',
    array(
        'version' => '1.0.0',
        'file'    => __FILE__,
    )
);
```

---

#### `should_lock_addon_features( $product_slug )`
Check if an add-on's features should be locked due to license issues.

**Parameters:**
- `$product_slug` (string) - The product slug

**Returns:** `bool` - True if features should be locked

**Example:**
```php
if ( BB_DRM_Registry::should_lock_addon_features( 'buddyboss-membership' ) ) {
    // Don't load membership features
    return;
}
```

---

#### `get_lockout_message( $product_slug )`
Get a formatted lockout message for display.

**Parameters:**
- `$product_slug` (string) - The product slug

**Returns:** `string` - HTML message to display

**Example:**
```php
$message = BB_DRM_Registry::get_lockout_message( 'buddyboss-docs' );
echo $message; // "BuddyBoss Docs features are currently disabled..."
```

---

#### `display_lockout_notice( $product_slug, $context )`
Display a formatted lockout notice.

**Parameters:**
- `$product_slug` (string) - The product slug
- `$context` (string) - Display context: 'admin_notice', 'inline', or 'modal'

**Example:**
```php
// Show as WordPress admin notice
BB_DRM_Registry::display_lockout_notice( 'buddyboss-app', 'admin_notice' );

// Show inline on a settings page
BB_DRM_Registry::display_lockout_notice( 'buddyboss-app', 'inline' );

// Show as a modal-style notice
BB_DRM_Registry::display_lockout_notice( 'buddyboss-app', 'modal' );
```

---

#### `get_addon_drm( $product_slug )`
Get the DRM instance for a specific add-on.

**Parameters:**
- `$product_slug` (string) - The product slug

**Returns:** `BB_DRM_Addon` instance or `null`

**Example:**
```php
$drm = BB_DRM_Registry::get_addon_drm( 'buddyboss-pusher' );
if ( $drm && $drm->is_addon_licensed() ) {
    // License is valid
}
```

---

### BB_DRM_Addon Methods

#### `is_addon_licensed()`
Check if this specific add-on has a valid license.

**Returns:** `bool` - True if licensed

**Example:**
```php
$drm = BB_DRM_Registry::get_addon_drm( 'buddyboss-elementor-sections' );
if ( $drm->is_addon_licensed() ) {
    // Load Elementor sections
}
```

---

#### `should_lock_features()`
Check if features should be locked (combines license check with DRM status).

**Returns:** `bool` - True if features should be locked

---

## DRM Timeline for Add-ons

Add-ons have a **faster timeline** than the core Platform:

```
Day 0:  License expires/deactivates
        └─ Event created

Day 7:  LOW status
        ├─ Warning notice
        ├─ Email sent
        └─ In-plugin notification

Day 14: MEDIUM status
        ├─ Urgent warning
        ├─ Email sent
        └─ In-plugin notification

Day 21: LOCKED status
        ├─ Features disabled
        ├─ Email sent
        └─ In-plugin notification
```

---

## Advanced Usage

### Conditional Feature Loading

```php
// Load only specific features based on license
if ( BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' ) ) {
    // Core features only
    $this->load_basic_features();
} else {
    // Full feature set
    $this->load_all_features();
}
```

### Custom Lockout Messages

```php
add_filter( 'bb_drm_addon_lockout_message_buddyboss-platform-pro', function( $message ) {
    return 'Custom message: Please renew your license to access Zoom integration.';
});
```

### Feature-Specific Checks

```php
function can_load_zoom_integration() {
    // Check if Platform Pro license allows Zoom
    if ( BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' ) ) {
        return false;
    }

    // Additional checks...
    return true;
}
```

---

## Migration Guide

### Migrating from Old License System

**Step 1: Keep existing code as fallback**
```php
private function check_license() {
    // Try new DRM system first
    if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
        return ! BB_DRM_Registry::should_lock_addon_features( 'your-product-slug' );
    }

    // Fall back to old system
    return $this->old_license_check();
}
```

**Step 2: Register with DRM**
```php
// Add this to your plugin initialization
if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
    BB_DRM_Registry::register_addon( 'your-product-slug', 'Your Plugin Name' );
}
```

**Step 3: Update feature loading**
```php
// Replace direct license checks
// OLD: if ( $this->is_licensed() )
// NEW: if ( ! BB_DRM_Registry::should_lock_addon_features( 'your-product-slug' ) )
```

**Step 4: Remove old license validation (optional)**
```php
// After testing, you can remove old License_Manager classes
// But keep them as fallback for older Platform versions
```

---

## Testing Checklist

- [ ] Register add-on during plugin initialization
- [ ] Features load normally with valid license
- [ ] Features locked when license invalid
- [ ] Lockout notice displays in admin
- [ ] Email notifications sent at each DRM stage
- [ ] In-plugin notifications appear
- [ ] Staging servers bypass license check
- [ ] License reactivation restores features immediately
- [ ] Multiple add-ons can coexist without conflict
- [ ] Fallback works if Platform doesn't have DRM

---

## Troubleshooting

### Issue: Features still loading when locked

**Check:**
1. Is `should_lock_addon_features()` called BEFORE loading features?
2. Is the return value used correctly? (it returns `true` when locked)
3. Is the product slug correct?

```php
// WRONG
if ( BB_DRM_Registry::should_lock_addon_features( 'wrong-slug' ) ) {
    $this->load_features(); // Loads when locked!
}

// CORRECT
if ( ! BB_DRM_Registry::should_lock_addon_features( 'correct-slug' ) ) {
    $this->load_features(); // Loads when NOT locked
}
```

### Issue: DRM not detecting add-on

**Solution:**
```php
// Check if registered
var_dump( BB_DRM_Registry::is_addon_registered( 'your-product-slug' ) );

// Check all registered add-ons
var_dump( BB_DRM_Registry::get_registered_addons() );
```

### Issue: Staging detection not working

**Solution:**
```php
// Test staging detection
$drm = BB_DRM_Registry::get_addon_drm( 'your-product-slug' );
var_dump( $drm->is_staging_server() ); // Should return true on staging
```

---

## Best Practices

1. **Register Early** - Register with DRM in your plugin's constructor
2. **Check Before Loading** - Always check license before loading features
3. **Provide Fallback** - Keep old license system as fallback
4. **Use Correct Slug** - Match Mothership product slug exactly
5. **Test Thoroughly** - Test all three DRM states (LOW/MEDIUM/LOCKED)
6. **Handle Gracefully** - Show helpful messages when features are locked
7. **Don't Block Core** - Lock premium features only, not basic functionality

---

## Support

For questions or issues with DRM integration:
- Check the main README.md for DRM system overview
- Review code examples in this guide
- Test using the Quick Start steps above
- Contact BuddyBoss development team

---

**Version:** 1.0.0
**Last Updated:** 2025-01-15
