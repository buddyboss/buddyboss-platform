# BuddyBoss Platform – Admin Refactor & Feature-Based Architecture Plan

## 1. Current Plugin Architecture Overview

### 1.1 Boot & Core Loader

- **Root plugin file** `bp-loader.php`:
  - Loads `src/bp-loader.php`.
  - Defines core constants (`BP_PLUGIN_DIR`, `BP_PLUGIN_URL`, `BP_PLATFORM_VERSION`, etc.).
  - Loads Composer autoload if present.
  - Spoofs BuddyPress/bbPress activation for compatibility.
  - Loads `class-buddypress.php`, which creates the main singleton via `buddypress()`.

- **`src/bp-loader.php`**:
  - Handles compatibility checks (PHP version, conflicting plugins).
  - Sets up `BP_REQUIRED_PHP_VERSION`, textdomains, and finalizes boot:
    - Instantiates `BuddyPress` (core object).
    - Loads `bbpress` (forums) if enabled.

### 1.2 Component-Based Structure (Current)

Under `src/` the plugin is organized by **components**:

- **Activity** – `bp-activity/`
  - `bp-activity-loader.php`, `bp-activity-functions.php`, `bp-activity-template.php`
  - `bp-activity-admin.php` (admin list screen)
  - `actions/`, `classes/`, `screens/`, `admin/`

- **Groups** – `bp-groups/`
  - `bp-groups-loader.php`, `bp-groups-functions.php`, `bp-groups-template.php`
  - `bp-groups-admin.php` (admin list/edit screen)
  - `classes/`, `screens/`, `admin/`

- Other components:
  - `bp-members/`, `bp-messages/`, `bp-media/`, `bp-forums/`, `bp-friends/`, `bp-notifications/`, `bp-video/`, `bp-search/`, `bp-performance/`, `bp-xprofile/`, `bp-document/`, etc.

- **Core framework** – `bp-core/`:
  - Core logic: `bp-core-loader.php`, `bp-core-actions.php`, `bp-core-functions.php`, `bp-core-options.php`, `bp-core-rest-api.php`, etc.
  - Admin framework & menus: `bp-core-admin.php`, `classes/class-bp-admin.php`, `classes/class-bp-admin-tab.php`, `admin/settings/…`.
  - Integrations, compatibility, widgets, template loader, GDPR, emails, etc.

## 2. Admin Menus & Settings – Current Behavior

### 2.1 “BuddyBoss” Admin Menu

- `src/bp-core/bp-core-admin.php`:
  - Defines `bp_admin()` which attaches `BP_Admin` instance to `buddypress()->admin`.

- `src/bp-core/classes/class-bp-admin.php`:
  - Responsible for **all BuddyBoss admin menus**:
    - Adds top-level **BuddyBoss** menu with `add_menu_page()`.
    - Adds submenus with `add_submenu_page()`:
      - Components (`bp-components`)
      - Pages (`bp-pages`)
      - Settings (`bp-settings`)
      - Integrations (`bp-integrations`)
      - Upgrade (`bb-upgrade`)
      - Credits (`bp-credits`)
      - ReadyLaunch (`bb-readylaunch`)
      - Tools (for multisite/network admin)
  - Uses helpers:
    - `bp_add_main_menu_page_admin_menu()` – to add top-level BuddyBoss menu.
    - `admin_menus()` – to register full set of submenus.

### 2.2 Component Admin Screens

- **Activity Admin**
  - `src/bp-activity/bp-activity-admin.php`:
    - Adds submenu `bp-activity` under `buddyboss-platform` via `add_submenu_page()`.
    - Uses `BP_Activity_List_Table` (extends `WP_List_Table`) to render classic WP-style list of activity items.
    - Implements actions: reply to activity, bulk operations, screen options.
    - Hooks:
      - `bp_activity_add_admin_menu` on `bp_core_admin_hook()`.
      - `bp_activity_admin_load` on `load-{hook}`.
      - AJAX handler `wp_ajax_bp-activity-admin-reply`.

- **Groups Admin**
  - `src/bp-groups/bp-groups-admin.php`:
    - Adds submenu `bp-groups` under `buddyboss-platform`.
    - Uses `BP_Groups_List_Table` (classic `WP_List_Table`) for groups listing.
    - Supports edit screen with metaboxes (settings, members, group type, group parent).
    - Hooks:
      - `bp_groups_add_admin_menu` on `bp_core_admin_hook()`.
      - `bp_groups_admin_load` on `load-{hook}`.
      - Uses various help tabs and screen options.

### 2.3 Settings System (Tabs, Sections, Fields)

- **Admin Tab Helper**
  - `src/bp-core/classes/class-bp-admin-tab.php`:
    - Provides a structured wrapper over the **WordPress Settings API**:
      - **Sections**:
        - `add_section( $id, $title, $callback, $tutorial_callback, $notice )`
        - Internally uses `add_settings_section()`.
        - Stores metadata: tutorial callback, notice, icon (via `bb_admin_icons()`).
      - **Fields**:
        - `add_field( $name, $label, $callback, $field_args, $callback_args, $id )`
        - Internally uses `add_settings_field()` + `register_setting()`.
      - Convenience helpers:
        - `add_input_field()`, `add_checkbox_field()`, etc.

- **Per-Component Settings Files**
  - Located in `src/bp-core/admin/settings/`:
    - `bp-admin-setting-activity.php`
      - Registers Activity settings (`bp_activity` section) and fields like:
        - `_bp_enable_activity_edit`
        - `bb_activity_post_title_enabled`
        - `_bp_activity_edit_time`
    - `bp-admin-setting-groups.php`
      - Registers Groups sections:
        - `bp_groups` – Group Settings.
        - `bp_groups_avatar_settings` – Group Images.
        - `bp_groups_headers_settings` – Group Headers.
        - `bp_groups_types` – Group Types.
        - `bp_groups_hierarchies` – Group Hierarchies.
      - Fields for creation, avatars, headers, directories, types, hierarchies, etc.
      - Uses `bb_get_pro_fields_class()` and related helpers to flag Pro/Plus features.
    - Other settings files for general, document, media, messages, notifications, performance, search, registration, etc.

- **General Settings Hub**
  - `bp-admin-setting-general.php`:
    - Registers “Overview” sections for Profiles, Groups, Activity, etc. via `add_settings_section()`.
    - Provides entry points for main tabs.

### 2.4 Integrations & Licensing (Pro/Plus)

- Licensing & add‑ons:
  - `src/bp-core/admin/mothership/` – licensing, product catalog, add‑on management.
  - `src/bp-core/admin/drm/` – DRM checks, notifications.

- Integrations:
  - `src/bp-integrations/` – integration implementations (e.g., LearnDash, MemberPress, etc.).
  - Existing settings surfaces these integrations in the Integrations tab and other areas.

### 2.5 Existing React / WordPress React Usage

- **ReadyLaunch & Onboarding React Apps**
  - `src/bp-core/admin/bb-settings/rl-onboarding/`:
    - `class-bb-readylaunch-onboarding.php` registers a React-based onboarding wizard.
    - Uses dependency arrays like:
      - `react_dependencies` → `array( 'react', 'wp-components', 'wp-element', 'wp-i18n' )`.
    - `build/rl-onboarding.asset.php` includes:
      - `'dependencies' => array( 'react', 'react-dom', 'wp-components', 'wp-element', 'wp-i18n' )`.

  - `src/bp-core/admin/bb-settings/readylaunch/build/index.asset.php`:
    - Uses `array( 'react', 'react-dom', 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' )`.

- This confirms:
  - The plugin already uses **WordPress React (Gutenberg packages)** for admin.
  - It uses WordPress’ asset registration pattern (`*.asset.php`) for dependency management.

## 3. Target Direction: Feature-Based Admin Architecture (Aligned With New Designs)

You want to:

- Move from **component‑based** organization to **feature‑based** organization.
- Keep **frontend behavior unchanged for now**.
- Completely redesign **admin UI in React using “WordPress React”**.
- Provide **first‑class extensibility** for third‑party plugins to:
  - Register new **features**.
  - Add **side navigation items**, **sections**, and **fields** within the new React admin.
- Ensure:
  - Old code is **properly removed and replaced**, not left as dead legacy.
  - PHP code follows **WordPress Coding Standards**.
  - React code uses `wp.element`, `wp.components`, `wp.apiFetch`, `wp.i18n`, etc.

High-level concept: Introduce a **Feature Registry + REST API + React Admin Shell**, while:

- Reusing the existing **React-based settings implementation** (new shell and components, same stack: WordPress React).
- **Keeping all existing option/setting names unchanged**, so settings save to the same options and third-party code remains compatible.

### 3.1 Code Compartmentalization & Conditional Loading

From the Settings design (`settings` frame, and "Disabled" status badges on feature cards, see [`Backend-Settings-2.0`](https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=3882-76894&m=dev)), we must ensure that when a feature is **disabled**:

- **PHP code for that feature is not bootstrapped** (no actions/filters, no class loading beyond a minimal registry stub).
- **React/JS bundles for that feature are not enqueued or imported** (no admin JS/CSS for a disabled feature).

**WHAT Should Happen**:

- Each feature in the registry must define:
  - `is_active_callback` – determines both **behavioural activation** and **whether its code should load**.
  - `php_loader` callback – a callable or class + method that attaches actions/filters for that feature; called only if `is_active_callback` returns true.
  - Optional `admin_script_handle` – React bundle handle for per-feature admin screens; enqueued only when:
    - The current admin route is within that feature's area (e.g. `/settings/activity`, `/settings/groups/all`), and
    - `is_active_callback` is true.
- Core bootstrap (`bp-core-loader.php` / component loaders) should be updated so that:
  - Heavy feature modules (e.g. Activity, Groups) are loaded via registry-driven conditional bootstrapping.
  - Minimal stubs remain to support `bp_is_active( 'activity' )` and similar checks without pulling in full modules.
- The React admin shell:
  - Always loads a **small core bundle** (Dashboard, Settings grid, nav, search).
  - Loads per-feature screens (Activity settings, Groups settings, All Activity, All Groups, Group Types, Group Navigation) via **code-splitting / dynamic import** tied to feature routes and `is_active` status.
  - When a feature is disabled:
    - Its card shows as **Disabled** (as in Figma).
    - Its routes are protected:
      - Either redirect back to Settings grid, or show a "feature disabled" notice.
      - Do **not** mount feature React components or load that bundle.

**HOW to Implement Code Compartmentalization**:

- **PHP Loading Strategy**:
  1. **Feature Registry Initialization**:
     - Feature Registry loads early (in `bp-core-loader.php` or `bp-core-admin.php`).
     - Registry reads all feature definitions but does NOT load feature code yet.
     - Registry checks `is_active_callback()` for each feature.
  
  2. **Conditional PHP Loading**:
```php
// In Feature Registry
class BB_Feature_Registry {
    private $features = array();
    
    public function register_feature( $id, $args ) {
        // Store feature definition
        $this->features[ $id ] = $args;
        
        // If feature is active, load its PHP code immediately
        if ( $this->is_feature_active( $id ) ) {
            $this->load_feature_php( $id );
        }
    }
    
    private function load_feature_php( $id ) {
        $feature = $this->features[ $id ];
        
        if ( isset( $feature['php_loader'] ) && is_callable( $feature['php_loader'] ) ) {
            // Call the loader callback
            call_user_func( $feature['php_loader'] );
        } elseif ( isset( $feature['php_loader'] ) && is_string( $feature['php_loader'] ) ) {
            // Load a file
            $file = $feature['php_loader'];
            if ( file_exists( $file ) ) {
                require_once $file;
            }
        } elseif ( isset( $feature['php_loader'] ) && is_array( $feature['php_loader'] ) ) {
            // Array format: [ 'class' => 'ClassName', 'method' => 'methodName' ]
            if ( isset( $feature['php_loader']['class'] ) && isset( $feature['php_loader']['method'] ) ) {
                $class  = $feature['php_loader']['class'];
                $method = $feature['php_loader']['method'];
                if ( class_exists( $class ) && method_exists( $class, $method ) ) {
                    call_user_func( array( $class, $method ) );
                }
            }
        }
    }
    
    public function on_feature_toggled( $id, $is_active ) {
        if ( $is_active ) {
            // Feature activated - load PHP code now
            $this->load_feature_php( $id );
        } else {
            // Feature deactivated - cannot unload PHP, but prevent new actions
            // Note: PHP cannot be truly "unloaded", but we can prevent new hooks
            do_action( "bb_feature_deactivated_{$id}" );
        }
    }
}
```

  3. **Component Loader Updates**:
```php
// In bp-activity/bp-activity-loader.php (example)
function bp_activity_setup_components() {
    // OLD: Always load
    // require_once dirname( __FILE__ ) . '/bp-activity-functions.php';
    // require_once dirname( __FILE__ ) . '/bp-activity-actions.php';
    
    // NEW: Check if feature is active via registry
    if ( ! bp_is_active( 'activity' ) ) {
        // Only load minimal stub for bp_is_active() checks
        require_once dirname( __FILE__ ) . '/bp-activity-stub.php';
        return;
    }
    
    // Feature is active - load full code
    require_once dirname( __FILE__ ) . '/bp-activity-functions.php';
    require_once dirname( __FILE__ ) . '/bp-activity-actions.php';
    require_once dirname( __FILE__ ) . '/bp-activity-screens.php';
    // ... etc
}
```

  4. **Autoloader Gates** (if using Composer autoloader):
```php
// In composer.json or custom autoloader
spl_autoload_register( function( $class ) {
    // SECURITY: Use strict regex patterns to prevent class name injection
    // Only allow alphanumeric, underscore, and backslash in class names
    if ( ! preg_match( '/^[a-zA-Z0-9_\\\\]+$/', $class ) ) {
        return false; // Reject invalid class names
    }
    
    // Check if class belongs to a feature
    // Use strict prefix matching with word boundary
    $feature_class_map = array(
        '/^BP_Activity_/' => 'activity',
        '/^BP_Groups_/'   => 'groups',
        '/^BP_Messages_/' => 'messages',
        // ... etc
    );
    
    foreach ( $feature_class_map as $pattern => $feature_id ) {
        if ( preg_match( $pattern, $class ) ) {
            // Only autoload if feature is active
            if ( ! bp_is_active( $feature_id ) ) {
                return false; // Don't load
            }
            break; // Found matching feature, stop checking
        }
    }
    
    // Continue with normal autoloading
    // ...
} );
```

**OPcache Impact of Conditional Loading**:
- **Problem**: PHP OPcache caches all loaded files. Conditional loading means some files may not be cached initially.
- **Impact**:
  - First request after feature activation may be slower (file not in OPcache)
  - OPcache may cache files for inactive features (wasted memory)
  - Cache invalidation needed when features are toggled
- **Solutions**:
```php
// 1. Pre-warm OPcache for active features
function bb_warm_opcache() {
    $active_features = bp_get_option( 'bp-active-components', array() );
    
    foreach ( $active_features as $feature ) {
        $files = bb_get_feature_files( $feature );
        foreach ( $files as $file ) {
            if ( file_exists( $file ) ) {
                opcache_compile_file( $file );
            }
        }
    }
}

// 2. Clear OPcache when feature is toggled
add_action( 'bb_feature_toggled', function( $feature_id, $is_active ) {
    if ( function_exists( 'opcache_reset' ) ) {
        // Note: opcache_reset() clears ALL cache - use sparingly
        // Better: Use opcache_invalidate() for specific files
        $files = bb_get_feature_files( $feature_id );
        foreach ( $files as $file ) {
            if ( function_exists( 'opcache_invalidate' ) ) {
                opcache_invalidate( $file, true );
            }
        }
    }
}, 10, 2 );

// 3. Monitor OPcache usage
function bb_check_opcache_status() {
    if ( ! function_exists( 'opcache_get_status' ) ) {
        return false;
    }
    
    $status = opcache_get_status();
    $memory_usage = $status['memory_usage'];
    $hit_rate = $status['opcache_statistics']['opcache_hit_rate'];
    
    // Log if hit rate is low or memory usage is high
    if ( $hit_rate < 90 || $memory_usage['used_memory'] > $memory_usage['free_memory'] ) {
        error_log( sprintf(
            'BuddyBoss OPcache: Hit rate: %.2f%%, Memory: %d/%d',
            $hit_rate,
            $memory_usage['used_memory'],
            $memory_usage['free_memory'] + $memory_usage['used_memory']
        ) );
    }
    
    return $status;
}
```

**Best Practices**:
- Pre-warm OPcache for active features on plugin activation
- Invalidate OPcache for toggled features (not full reset)
- Monitor OPcache hit rate and memory usage
- Consider OPcache configuration: `opcache.max_accelerated_files` should accommodate all feature files
- Document OPcache requirements in system requirements

**OPcache Impact of Conditional Loading**:
- **Problem**: PHP OPcache caches all loaded files. Conditional loading means some files may not be cached initially.
- **Impact**:
  - First request after feature activation may be slower (file not in OPcache)
  - OPcache may cache files for inactive features (wasted memory)
  - Cache invalidation needed when features are toggled
- **Solutions**:
```php
// 1. Pre-warm OPcache for active features
function bb_warm_opcache() {
    $active_features = bp_get_option( 'bp-active-components', array() );
    
    foreach ( $active_features as $feature ) {
        $files = bb_get_feature_files( $feature );
        foreach ( $files as $file ) {
            if ( file_exists( $file ) ) {
                opcache_compile_file( $file );
            }
        }
    }
}

// 2. Clear OPcache when feature is toggled
add_action( 'bb_feature_toggled', function( $feature_id, $is_active ) {
    if ( function_exists( 'opcache_reset' ) ) {
        // Note: opcache_reset() clears ALL cache - use sparingly
        // Better: Use opcache_invalidate() for specific files
        $files = bb_get_feature_files( $feature_id );
        foreach ( $files as $file ) {
            if ( function_exists( 'opcache_invalidate' ) ) {
                opcache_invalidate( $file, true );
            }
        }
    }
}, 10, 2 );

// 3. Monitor OPcache usage
function bb_check_opcache_status() {
    if ( ! function_exists( 'opcache_get_status' ) ) {
        return false;
    }
    
    $status = opcache_get_status();
    $memory_usage = $status['memory_usage'];
    $hit_rate = $status['opcache_statistics']['opcache_hit_rate'];
    
    // Log if hit rate is low or memory usage is high
    if ( $hit_rate < 90 || $memory_usage['used_memory'] > $memory_usage['free_memory'] ) {
        error_log( sprintf(
            'BuddyBoss OPcache: Hit rate: %.2f%%, Memory: %d/%d',
            $hit_rate,
            $memory_usage['used_memory'],
            $memory_usage['free_memory'] + $memory_usage['used_memory']
        ) );
    }
    
    return $status;
}
```

**Best Practices**:
- Pre-warm OPcache for active features on plugin activation
- Invalidate OPcache for toggled features (not full reset)
- Monitor OPcache hit rate and memory usage
- Consider OPcache configuration: `opcache.max_accelerated_files` should accommodate all feature files
- Document OPcache requirements in system requirements

  5. **Action/Filter Registration Gates**:
```php
// In feature's php_loader callback
function bp_activity_register_hooks() {
    // Only register hooks if feature is active
    if ( ! bp_is_active( 'activity' ) ) {
        return;
    }
    
    add_action( 'bp_init', 'bp_activity_setup_globals' );
    add_filter( 'bp_get_activity_content', 'bp_activity_format_content' );
    // ... etc
}
```

- **React Bundle Loading Strategy**:
  1. **Shell Bundle** (always loaded):
     - Contains: routing, layout, Dashboard, Settings grid
     - Enqueued on all BuddyBoss admin pages
  
  2. **Feature Bundles** (lazy-loaded):
```javascript
// In React router
const ActivitySettings = lazy(() => {
    // Check if feature is active before loading
    return wp.apiFetch({ path: '/buddyboss/v1/features/activity' })
        .then(feature => {
            if (feature.status === 'active') {
                return import(/* webpackChunkName: "feature-activity" */ './features/activity/SettingsScreen');
            } else {
                // Return a "Feature Disabled" component
                return import('./components/FeatureDisabled');
            }
        });
});
```

  3. **Route Protection**:
```javascript
// In React router guard
function FeatureRoute({ featureId, children }) {
    const [feature, setFeature] = useState(null);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        wp.apiFetch({ path: `/buddyboss/v1/features/${featureId}` })
            .then(setFeature)
            .finally(() => setLoading(false));
    }, [featureId]);
    
    if (loading) return <Spinner />;
    if (feature.status !== 'active') {
        return <FeatureDisabled feature={feature} />;
    }
    return children;
}
```

- **Implementation Checklist**:
  - [ ] Update Feature Registry to call `php_loader` only when `is_active_callback()` returns true
  - [ ] Update component loaders to check `bp_is_active()` before loading full code
  - [ ] Create minimal stubs for each component (for `bp_is_active()` checks)
  - [ ] Update autoloader to gate class loading based on feature status
  - [ ] Update React router to lazy-load feature bundles only when active
  - [ ] Add route guards to prevent access to disabled feature screens
  - [ ] Test: Disable a feature and verify no PHP code loads
  - [ ] Test: Disable a feature and verify no React bundle loads
  - [ ] Test: Re-enable feature and verify code loads correctly

### 3.2 Replacing `page=bp-components` with Feature-Based Toggles

Currently, `BuddyBoss → Components` (`admin.php?page=bp-components`) exposes a **component list** where admins can enable/disable core components (Activity, Groups, etc.). In the new architecture:

- The **Components page is reimplemented as feature-based toggles**, visually matching the feature grid in the Settings design ([`Backend-Settings-2.0`](https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=3882-76894&m=dev)):
  - Each **component** becomes one or more **features** (e.g. Activity, Groups, Messages, Media).
  - Each feature card has an **Active/Disabled** state and can be toggled.

- **Option compatibility**:
  - We **do not change** the underlying options used today by `bp-components`:
    - E.g. the `bp-active-components` array and any related toggles.
  - In the Feature Registry:
    - Features that map to components (Activity, Groups, etc.) use `is_active_callback` and `load_php()` that read/write the exact same options.
  - When a feature card is toggled:
    - The React UI calls `POST /buddyboss/v1/features/{feature}/toggle` with `{ active: true|false }`.
    - The REST controller:
      - Updates the **existing** component options (e.g., `bp-active-components` array).
      - Ensures `bp_is_active( 'activity' )`, `bp_is_active( 'groups' )`, etc. continue to work unchanged.
      - Clears relevant caches (transients, object cache).
      - Fires action hooks: `bb_feature_activated_{$feature}` or `bb_feature_deactivated_{$feature}`.
      - Returns updated feature status to React UI.

- **Admin routing & UI**:
  - Keep the `bp-components` submenu entry for compatibility, but:
    - Change its callback to **mount the React admin shell** instead of the old PHP components table.
    - Either:
      - Render the same feature grid used for `BuddyBoss → Settings`, filtered/highlighted for component-like features, or
      - Redirect `admin.php?page=bp-components` into the SPA route such as `admin.php?page=buddyboss-platform#/settings?tab=components`.
  - The React shell:
    - Uses `GET /buddyboss/v1/features` to show cards and current Active/Disabled status.
    - Uses the same enable/disable logic regardless of whether you arrive from **Settings** or **Components**.

- **Code loading**:
  - Because feature activation funnels through the same options:
    - Disabling a feature / component via this screen:
      - Updates `bp-active-components` array (removes feature ID).
      - Marks the feature as inactive in the registry.
      - Prevents `load_php()` from running for that feature (no PHP code loaded).
      - Prevents per-feature React bundles from being lazy-loaded.
      - Clears feature-specific caches.
      - Fires `bb_feature_deactivated_{$feature}` action hook.
    - Enabling a feature / component:
      - Updates `bp-active-components` array (adds feature ID).
      - Marks the feature as active in the registry.
      - Re-enables its PHP bootstrap (calls `load_php()` callback if provided).
      - Allows per-feature React bundles to be lazy-loaded.
      - Clears feature-specific caches.
      - Fires `bb_feature_activated_{$feature}` action hook.

- **Component activation mechanism details**:
  - **For core components** (Activity, Groups, etc.):
    - Feature Registry reads `bp-active-components` option (array of active component IDs).
    - `is_active_callback` checks: `in_array( 'activity', bp_get_option( 'bp-active-components', array() ) )`.
    - Toggle endpoint updates this array: adds/removes component ID.
    - Existing `bp_is_active( 'activity' )` function continues to work (it checks same option).
  - **For add-on features** (Platform Pro, Gamification, etc.):
    - May use different options (e.g., `bb_pro_zoom_enabled`, `bb_gm_enabled`).
    - `is_active_callback` checks the appropriate option.
    - Toggle endpoint updates that specific option.
  - **Hooks fired**:
    - `bb_feature_activated_{$feature_id}` – When feature is enabled.
    - `bb_feature_deactivated_{$feature_id}` – When feature is disabled.
    - `bb_feature_toggled` – Generic hook with `$feature_id` and `$is_active` parameters.

## 4. Feature Registry (Backend, PHP)

### 4.0 Icon Registry (Custom Icon Support)

**Icon Registry**: A system for registering custom icons that can be used in feature registration, separate from Dashicons.

- **Purpose**: Allow third-party plugins to use custom SVG, image, font, or React component icons.
- **Registration Function**: `bb_register_icon( string $icon_id, array $args )`
- **Hook**: `bb_register_icons` – Fired when registering custom icons (register your icons here).
- **Icon Types Supported**:
  - **SVG**: URL to SVG file or data URI
  - **Image**: URL to PNG/JPG/GIF file  
  - **Font**: CSS class for icon font
  - **React Component**: Custom React component
  - **Dashicons**: Built-in WordPress icons (no registration needed)

- **Example Registration**:
```php
add_action( 'bb_register_icons', 'my_plugin_register_icons' );
function my_plugin_register_icons() {
    // Register SVG icon
    bb_register_icon( 'my-plugin-icon', array(
        'type'        => 'svg',
        'url'         => plugin_dir_url( __FILE__ ) . 'assets/icons/icon.svg',
        'width'       => 24,
        'height'      => 24,
        'description' => __( 'My Plugin Icon', 'my-plugin' ),
    ) );
    
    // Register image icon
    bb_register_icon( 'my-plugin-logo', array(
        'type' => 'image',
        'url'  => plugin_dir_url( __FILE__ ) . 'assets/images/logo.png',
    ) );
    
    // Register font icon
    bb_register_icon( 'my-plugin-font', array(
        'type'  => 'font',
        'class' => 'my-plugin-icon-class',
    ) );
}
```

- **Usage in Features**: Use registered icon ID in feature registration:
```php
bb_register_feature( 'my-feature', array(
    'icon' => 'my-plugin-icon', // Uses registered icon
    // ...
) );
```

See section 5.6 "Implementation Details" for Icon Handling & Custom Icon Support documentation.

### 4.1 Concept

A central **Feature Registry** describes all BuddyBoss admin features and their settings. It replaces ad-hoc "component" registrations and scattered settings declarations with a single, structured source of truth.

- Each **feature**:
  - Has metadata (id, label, description, icon, category).
  - Knows whether it is **Free/Pro/Plus** and whether it is active.
  - Exposes **sections** and **fields** that will appear in the React UI.

### 4.2 Feature Registration API

Proposed functions (final naming can follow your conventions):

- **Feature**
  - `bb_register_feature( string $id, array $args );`

  **Example args:**

  - `label` – Human-readable name (e.g. “Activity”).
  - `description` – Short description for feature list card.
  - `icon` – Icon identifier (supports multiple formats):
    - **Dashicon slug**: `'dashicons-groups'`, `'dashicons-admin-settings'`, etc.
    - **Custom icon ID**: Registered via `bb_register_icon()` (e.g., `'my-plugin-icon'`).
    - **SVG URL**: Full URL to SVG file (e.g., `'https://example.com/icons/icon.svg'`).
    - **SVG Data URI**: `'data:image/svg+xml;base64,...'`.
    - **Image URL**: Full URL to PNG/JPG file.
    - **Local path**: Path relative to plugin directory (converted to URL automatically).
    - **React component name**: For custom React icon components.
    - See section 5.6 "Implementation Details" for Icon Handling & Custom Icon Support detailed examples.
  - `category` – e.g. "Community", "Engagement", "Moderation".
  - `license_tier` – `free`, `pro`, `plus`.
  - `is_available_callback` – Returns whether feature is available under current license:
    - Should check license status (e.g., `bb_pro_is_license_valid()`, `bb_gm_is_license_valid()`).
    - Returns `false` if feature requires Pro/Plus but license is invalid.
    - Used to show/hide features in Settings grid and disable toggles.
  - `is_active_callback` – Returns whether feature is active (often based on an option, e.g., checks `bp_is_active( 'activity' )`).
  - `settings_route` – Path in SPA (e.g. `/settings/activity`).
  - `activation_option` – Optional: Name of option to update when toggling (e.g., `'bp-active-components'`). If not provided, registry infers from `is_active_callback`.
  - `activation_value` – Optional: Value to set in option when activating (default: feature ID added to array or `true`).
  - `dependencies` – Optional: Array of feature IDs that must be active before this feature can be activated (e.g., `array( 'activity' )`).
  - `on_activate` – Optional: Callback fired when feature is activated (for side effects like creating pages, setting up cron jobs).
  - `on_deactivate` – Optional: Callback fired when feature is deactivated (for cleanup like removing cron jobs, cleaning up data).

- **Sections**
  - `bb_register_feature_section( string $feature_id, string $section_id, array $args );`

  **Example args:**

  - `title` – Section title (e.g. “Activity Settings”).
  - `description` – Optional description text.
  - `nav_group` – For left-side nav grouping (“Activity”, “Access Control”, “Extensions”, etc.).
  - `order` – Numeric sort order.
  - `is_default` – Whether this should be opened first.

- **Fields**
  - `bb_register_feature_field( string $feature_id, string $section_id, array $field_args );`

  **Example `field_args`:**

  - `name` – Option key / setting name.
  - `label` – Field label.
  - `type` – `checkbox`, `toggle`, `select`, `text`, `textarea`, `radio`, `number`, `email`, `url`, `date`, `time`, `color`, `media`, `repeater`, `field_group`, etc. (see section 4.6 for complex types).
  - `description` – Help text.
  - `default` – Default value.
  - `sanitize_callback` – Callback used with `register_setting()`.
  - `validate_callback` – Optional: Additional validation beyond sanitization (see section 4.7 for validation schema).
  - `render_type` or `control` – Additional hints for React control.
  - `pro_only` / `license_tier` – For gating UI.
  - `options` – For selects/radios.
  - `conditional` – Conditional display logic (see section 4.8 for conditional fields).

- **Navigation Items** (for side panel)
  - `bb_register_feature_nav_item( string $feature_id, array $args );`

  **Example `args`:**

  - `id` – Unique identifier for the nav item.
  - `label` – Display text in sidebar.
  - `route` – React route path (e.g., `/activity/my-plugin-log`).
  - `icon` – Icon identifier (supports Dashicons, custom registered icons, SVG URLs, image URLs, React components - see section 5.6 for Icon Handling details).
  - `nav_group` – Group name in sidebar (defaults to feature name).
  - `order` – Numeric sort order within `nav_group` (default: 100).
  - `badge` – Optional badge count or text.
  - `is_external` – If `true`, opens in new tab.
  - `capability` – Required capability to see this nav item.
  - `is_active_callback` – Callback that returns `true` if item should be visible.

Under the hood, when you call `bb_register_feature_field`, the registry can also:

- Call `register_setting()` with the proper option name and sanitize callback.
- Store metadata in a structure that is exposed via REST.

When you call `bb_register_feature_nav_item`, the registry:

- Stores the navigation item in the feature's navigation array.
- Exposes it via REST API in the `navigation` field of `GET /features/{featureId}`.
- React automatically renders it in the left sidebar.

### 4.3 Feature Conflict Resolution & Namespacing

**Problem**: Multiple plugins may try to register the same `feature_id`, `section_id`, or `field_name`, causing conflicts.

**Solution**: Implement namespace-based conflict resolution:

- **Feature ID Namespacing**:
  - Core features use simple IDs: `'activity'`, `'groups'`, `'messages'`
  - Third-party plugins MUST use prefixed IDs: `'my-plugin-activity'`, `'my-plugin-custom-feature'`
  - Registry validates and rejects conflicts:
```php
class BB_Feature_Registry {
    private $registered_features = array();
    private $registered_sections = array(); // Key: "feature_id:section_id"
    private $registered_fields = array();   // Key: "feature_id:section_id:field_name"
    
    public function register_feature( $id, $args ) {
        // Validate ID format for third-party plugins
        if ( ! $this->is_core_feature( $id ) ) {
            // Third-party feature must be prefixed with plugin identifier
            $prefix = $this->get_plugin_prefix();
            if ( strpos( $id, $prefix ) !== 0 ) {
                $id = $prefix . '-' . $id;
                trigger_error( 
                    sprintf( 
                        'Feature ID "%s" must be prefixed. Using "%s" instead.', 
                        $args['original_id'] ?? $id, 
                        $id 
                    ), 
                    E_USER_WARNING 
                );
            }
        }
        
        // Check for conflicts
        if ( isset( $this->registered_features[ $id ] ) ) {
            // Conflict detected
            $existing = $this->registered_features[ $id ];
            
            // Strategy 1: Warn and append suffix
            $suffix = '-' . uniqid();
            $id = $id . $suffix;
            trigger_error( 
                sprintf( 
                    'Feature ID conflict: "%s" already registered. Using "%s" instead.', 
                    $args['original_id'] ?? $id, 
                    $id 
                ), 
                E_USER_WARNING 
            );
            
            // Strategy 2: Allow override if same plugin (update existing)
            // Strategy 3: Reject and log error (strict mode)
        }
        
        $this->registered_features[ $id ] = $args;
    }
    
    private function get_plugin_prefix() {
        // Get plugin identifier from current plugin
        $plugin_file = plugin_basename( __FILE__ );
        $plugin_slug = dirname( $plugin_file );
        return sanitize_key( $plugin_slug );
    }
    
    private function is_core_feature( $id ) {
        $core_features = array( 'activity', 'groups', 'messages', 'media', 'forums', /* ... */ );
        return in_array( $id, $core_features, true );
    }
}
```

- **Section ID Conflict Resolution**:
```php
public function register_feature_section( $feature_id, $section_id, $args ) {
    $key = "{$feature_id}:{$section_id}";
    
    if ( isset( $this->registered_sections[ $key ] ) ) {
        // Conflict - append plugin prefix
        $prefix = $this->get_plugin_prefix();
        $section_id = $prefix . '-' . $section_id;
        $key = "{$feature_id}:{$section_id}";
        
        trigger_error( 
            sprintf( 
                'Section ID conflict in feature "%s": Using "%s" instead.', 
                $feature_id, 
                $section_id 
            ), 
            E_USER_WARNING 
        );
    }
    
    $this->registered_sections[ $key ] = $args;
}
```

- **Field Name Conflict Resolution**:
```php
public function register_feature_field( $feature_id, $section_id, $field_args ) {
    $field_name = $field_args['name'];
    $key = "{$feature_id}:{$section_id}:{$field_name}";
    
    if ( isset( $this->registered_fields[ $key ] ) ) {
        // Conflict detected - DO NOT auto-rename
        // Field names are option keys and must remain stable
        $existing = $this->registered_fields[ $key ];
        
        // Log error and reject registration
        $error_message = sprintf(
            'Field name conflict: Field "%s" in feature "%s", section "%s" is already registered. ' .
            'Field names must be unique as they map to option keys. ' .
            'Please use a different field name or check if the field is already registered.',
            $field_name,
            $feature_id,
            $section_id
        );
        
        // In strict mode, throw exception
        if ( defined( 'BB_STRICT_FIELD_NAMES' ) && BB_STRICT_FIELD_NAMES ) {
            throw new Exception( $error_message );
        }
        
        // Otherwise, log warning and skip registration
        trigger_error( $error_message, E_USER_WARNING );
        
        // Return false to indicate registration failed
        return false;
    }
    
    $this->registered_fields[ $key ] = $field_args;
    return true;
}
```

- **Best Practices for Third-Party Plugins**:
```php
// GOOD: Use plugin prefix
bb_register_feature( 'my-plugin-activity-extension', array( /* ... */ ) );

// BAD: Generic ID (will be auto-prefixed with warning)
bb_register_feature( 'activity-extension', array( /* ... */ ) );

// GOOD: Use descriptive, prefixed IDs
bb_register_feature_section( 'activity', 'my-plugin-advanced-settings', array( /* ... */ ) );

// BAD: Generic section ID
bb_register_feature_section( 'activity', 'advanced-settings', array( /* ... */ ) );
```

- **Conflict Detection API**:
```php
// Check if feature/section/field already exists
bb_feature_exists( $feature_id );
bb_section_exists( $feature_id, $section_id );
bb_field_exists( $feature_id, $section_id, $field_name );

// Get conflict information
bb_get_feature_conflicts(); // Returns array of conflicts
```

### 4.4 Feature Dependencies

**Problem**: Some features depend on other features (e.g., Groups may need Activity for activity feeds).

**Solution**: Implement dependency declaration and validation:

- **Dependency Declaration**:
```php
bb_register_feature( 'groups', array(
    'label'       => __( 'Groups', 'buddyboss' ),
    'dependencies' => array( 'activity' ), // Groups requires Activity
    // ... other args
) );

bb_register_feature( 'my-plugin-feature', array(
    'label'       => __( 'My Feature', 'my-plugin' ),
    'dependencies' => array( 'activity', 'groups' ), // Requires both
    // ... other args
) );
```

- **Dependency Validation**:
```php
class BB_Feature_Registry {
    public function validate_dependencies( $feature_id ) {
        $feature = $this->features[ $feature_id ];
        
        if ( ! isset( $feature['dependencies'] ) || empty( $feature['dependencies'] ) ) {
            return true; // No dependencies
        }
        
        $missing = array();
        foreach ( $feature['dependencies'] as $dep_id ) {
            // Check if dependency exists
            if ( ! isset( $this->features[ $dep_id ] ) ) {
                $missing[] = $dep_id . ' (not registered)';
                continue;
            }
            
            // Check if dependency is active
            if ( ! $this->is_feature_active( $dep_id ) ) {
                $missing[] = $dep_id . ' (inactive)';
            }
        }
        
        if ( ! empty( $missing ) ) {
            return new WP_Error( 
                'missing_dependencies', 
                sprintf( 
                    'Feature "%s" requires the following features to be active: %s', 
                    $feature_id, 
                    implode( ', ', $missing ) 
                ),
                array( 'missing' => $missing )
            );
        }
        
        return true;
    }
    
    public function can_activate_feature( $feature_id ) {
        // Check dependencies
        $validation = $this->validate_dependencies( $feature_id );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }
        
        // Check license
        if ( ! $this->is_feature_available( $feature_id ) ) {
            return new WP_Error( 
                'license_required', 
                sprintf( 'Feature "%s" requires a valid license.', $feature_id )
            );
        }
        
        return true;
    }
}
```

- **Dependency Resolution in UI**:
  - When user tries to activate a feature with missing dependencies:
    - Show error message listing missing dependencies
    - Offer to activate dependencies automatically
    - Disable feature toggle until dependencies are met
  - In React UI:
```javascript
async function activateFeature(featureId) {
    const result = await wp.apiFetch({
        path: `/buddyboss/v1/features/${featureId}/toggle`,
        method: 'POST',
        data: { active: true }
    });
    
    if (result.error === 'missing_dependencies') {
        // Show modal: "This feature requires: Activity, Groups. Activate them?"
        showDependencyModal(result.missing);
    }
}
```

- **Circular Dependency Detection**:
```php
public function detect_circular_dependencies() {
    $visited = array();
    $recursion_stack = array();
    
    foreach ( $this->features as $id => $feature ) {
        if ( $this->has_circular_dependency( $id, $visited, $recursion_stack ) ) {
            trigger_error( 
                sprintf( 'Circular dependency detected involving feature: %s', $id ), 
                E_USER_WARNING 
            );
        }
    }
}

/**
 * Check if a feature has a circular dependency using DFS (Depth-First Search).
 * 
 * @param string $feature_id Feature ID to check.
 * @param array $visited Array of visited features (by reference).
 * @param array $recursion_stack Array of features in current recursion path (by reference).
 * @return bool True if circular dependency detected.
 */
private function has_circular_dependency( $feature_id, &$visited, &$recursion_stack ) {
    // Mark current feature as visited
    $visited[ $feature_id ] = true;
    
    // Add to recursion stack (current path)
    $recursion_stack[ $feature_id ] = true;
    
    // Get feature dependencies
    $feature = $this->features[ $feature_id ] ?? null;
    if ( ! $feature || ! isset( $feature['dependencies'] ) || empty( $feature['dependencies'] ) ) {
        // No dependencies - remove from recursion stack and return false
        unset( $recursion_stack[ $feature_id ] );
        return false;
    }
    
    // Check each dependency
    foreach ( $feature['dependencies'] as $dep_id ) {
        // If dependency is in recursion stack, we found a cycle
        if ( isset( $recursion_stack[ $dep_id ] ) ) {
            // Circular dependency detected
            return true;
        }
        
        // If not visited, recursively check this dependency
        if ( ! isset( $visited[ $dep_id ] ) ) {
            if ( $this->has_circular_dependency( $dep_id, $visited, $recursion_stack ) ) {
                return true;
            }
        }
    }
    
    // Remove from recursion stack (backtrack)
    unset( $recursion_stack[ $feature_id ] );
    
    return false;
}
```

### 4.5 Third-Party Extensibility

Third-party plugins integrate via:

- **Hook**:
  - `do_action( 'bb_register_features' );` – fired once the built-in features are registered.
- In this hook, they can call:
  - `bb_register_feature()` – Register a new feature card (MUST use prefixed IDs).
  - `bb_register_feature_section()` – Add a settings section (automatically appears in sidebar).
  - `bb_register_feature_field()` – Add a settings field within a section.
  - `bb_register_feature_nav_item()` – Add a custom navigation item to the side panel.

This allows:

- New **features** to appear on the Settings feature list page.
- New **sections** to appear in left-side navigation for existing features (e.g., an Activity extension).
- New **fields** to appear within those sections.
- New **navigation items** to appear in the side panel (for custom screens, list views, reports, etc.).
- All registered items are **automatically rendered by React** – no frontend code required.

### 4.6 Complex Field Types

Beyond basic field types (`text`, `toggle`, `select`), the Feature Registry supports advanced field types:

- **Repeater Fields** (for arrays of data):
```php
bb_register_feature_field( 'activity', 'activity_sharing', array(
    'name'  => 'bb_activity_sharing_networks',
    'label' => __( 'Social Networks', 'buddyboss' ),
    'type'  => 'repeater',
    'fields' => array(
        array(
            'name'  => 'network',
            'label' => __( 'Network', 'buddyboss' ),
            'type'  => 'select',
            'options' => array(
                'twitter' => 'Twitter',
                'facebook' => 'Facebook',
                'linkedin' => 'LinkedIn',
            ),
        ),
        array(
            'name'  => 'enabled',
            'label' => __( 'Enabled', 'buddyboss' ),
            'type'  => 'toggle',
        ),
    ),
    'default' => array(),
) );
```

- **Field Groups** (for grouped fields):
```php
bb_register_feature_field( 'groups', 'group_settings', array(
    'name'  => 'bb_group_cover_image',
    'label' => __( 'Cover Image Settings', 'buddyboss' ),
    'type'  => 'field_group',
    'fields' => array(
        array(
            'name'  => 'bb_group_cover_image_enabled',
            'label' => __( 'Enable Cover Images', 'buddyboss' ),
            'type'  => 'toggle',
        ),
        array(
            'name'  => 'bb_group_cover_image_width',
            'label' => __( 'Width (px)', 'buddyboss' ),
            'type'  => 'number',
            'min'   => 100,
            'max'   => 2000,
        ),
        array(
            'name'  => 'bb_group_cover_image_height',
            'label' => __( 'Height (px)', 'buddyboss' ),
            'type'  => 'number',
            'min'   => 100,
            'max'   => 2000,
        ),
    ),
) );
```

- **Media Picker**:
```php
bb_register_feature_field( 'groups', 'group_images', array(
    'name'  => 'bb_group_default_avatar',
    'label' => __( 'Default Group Avatar', 'buddyboss' ),
    'type'  => 'media',
    'mime_type' => 'image', // 'image', 'video', 'audio', or array of mime types
    'button_text' => __( 'Select Image', 'buddyboss' ),
) );
```

- **Color Picker**:
```php
bb_register_feature_field( 'activity', 'activity_appearance', array(
    'name'  => 'bb_activity_highlight_color',
    'label' => __( 'Highlight Color', 'buddyboss' ),
    'type'  => 'color',
    'default' => '#0073aa',
) );
```

- **Date/Time Picker**:
```php
bb_register_feature_field( 'activity', 'activity_scheduling', array(
    'name'  => 'bb_activity_scheduled_date',
    'label' => __( 'Scheduled Date', 'buddyboss' ),
    'type'  => 'datetime', // or 'date', 'time'
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
) );
```

- **Rich Text Editor** (TinyMCE):
```php
bb_register_feature_field( 'activity', 'activity_settings', array(
    'name'  => 'bb_activity_welcome_message',
    'label' => __( 'Welcome Message', 'buddyboss' ),
    'type'  => 'richtext',
    'media_buttons' => true,
    'textarea_rows' => 10,
) );
```

- **Code Editor**:
```php
bb_register_feature_field( 'activity', 'activity_advanced', array(
    'name'  => 'bb_activity_custom_css',
    'label' => __( 'Custom CSS', 'buddyboss' ),
    'type'  => 'code',
    'language' => 'css', // 'css', 'javascript', 'php', 'html', etc.
) );
```

- **React Component Rendering**:
```php
bb_register_feature_field( 'activity', 'activity_custom', array(
    'name'            => 'bb_activity_custom_field',
    'label'           => __( 'Custom Field', 'buddyboss' ),
    'type'            => 'custom',
    'render_component' => 'MyPluginCustomField', // React component name
    'render_props'    => array( 
        'some' => 'data',
        'config' => array( /* ... */ ),
    ),
) );
```

### 4.7 Settings Validation Schema

Beyond basic `sanitize_callback`, fields can define comprehensive validation rules:

- **Validation Schema Structure**:
```php
bb_register_feature_field( 'activity', 'activity_settings', array(
    'name'  => 'bb_activity_items_per_page',
    'label' => __( 'Items Per Page', 'buddyboss' ),
    'type'  => 'number',
    'default' => 20,
    'sanitize_callback' => 'absint',
    'validate_callback' => function( $value, $field ) {
        $errors = array();
        
        // Min/Max validation
        if ( $value < 1 ) {
            $errors[] = __( 'Value must be at least 1.', 'buddyboss' );
        }
        if ( $value > 100 ) {
            $errors[] = __( 'Value cannot exceed 100.', 'buddyboss' );
        }
        
        return empty( $errors ) ? true : $errors;
    },
    // Or use validation schema
    'validation' => array(
        'required' => true,
        'min'      => 1,
        'max'      => 100,
        'type'     => 'integer',
    ),
) );
```

- **Validation Schema Options**:
```php
'validation' => array(
    // Required field
    'required' => true,
    'required_message' => __( 'This field is required.', 'buddyboss' ),
    
    // Type validation
    'type' => 'string', // 'string', 'integer', 'number', 'boolean', 'array', 'object', 'email', 'url'
    
    // String validation
    'min_length' => 3,
    'max_length' => 255,
    'pattern'    => '/^[a-z0-9-]+$/', // Regex pattern
    'pattern_message' => __( 'Only lowercase letters, numbers, and hyphens allowed.', 'buddyboss' ),
    
    // Number validation
    'min' => 0,
    'max' => 100,
    'step' => 1, // For number inputs
    
    // Array validation
    'min_items' => 1,
    'max_items' => 10,
    'item_type' => 'string', // Type of array items
    
    // Custom validator function
    'validator' => function( $value, $field, $all_values ) {
        // Custom validation logic
        // Return true if valid, or error message string if invalid
        if ( $value === 'forbidden' ) {
            return __( 'This value is not allowed.', 'buddyboss' );
        }
        return true;
    },
    
    // Async validation (client-side)
    'async_validator' => array(
        'endpoint' => '/buddyboss/v1/validate/field',
        'method'   => 'POST',
    ),
),
```

- **Validation in REST API**:
```php
// In REST controller
public function update_settings( $request ) {
    $settings = $request->get_json_params();
    $errors = array();
    
    foreach ( $settings as $field_name => $value ) {
        $field = $this->registry->get_field( $feature_id, $section_id, $field_name );
        
        // Run validation
        $validation = $this->validate_field( $field, $value, $settings );
        if ( is_wp_error( $validation ) ) {
            $errors[ $field_name ] = $validation->get_error_message();
        }
    }
    
    if ( ! empty( $errors ) ) {
        return new WP_Error( 
            'validation_failed', 
            __( 'Validation failed.', 'buddyboss' ),
            array( 'errors' => $errors )
        );
    }
    
    // Save settings
    // ...
}
```

### 4.8 Conditional Field Display

Fields can be shown/hidden based on other field values:

- **Conditional Logic Syntax**:
```php
bb_register_feature_field( 'activity', 'activity_sharing', array(
    'name'  => 'bb_activity_sharing_enabled',
    'label' => __( 'Enable Activity Sharing', 'buddyboss' ),
    'type'  => 'toggle',
) );

bb_register_feature_field( 'activity', 'activity_sharing', array(
    'name'  => 'bb_activity_sharing_networks',
    'label' => __( 'Social Networks', 'buddyboss' ),
    'type'  => 'repeater',
    'conditional' => array(
        'field'   => 'bb_activity_sharing_enabled', // Field to watch
        'compare' => '==', // Comparison operator
        'value'   => true, // Value to compare against
    ),
) );
```

- **Comparison Operators**:
  - `==` or `equals` – Equal to
  - `!=` or `not_equals` – Not equal to
  - `>` – Greater than
  - `>=` – Greater than or equal
  - `<` – Less than
  - `<=` – Less than or equal
  - `in` – Value is in array
  - `not_in` – Value is not in array
  - `contains` – String contains substring
  - `regex` – Matches regex pattern

- **Complex Conditions** (AND/OR logic):
```php
bb_register_feature_field( 'groups', 'group_settings', array(
    'name'  => 'bb_group_advanced_feature',
    'label' => __( 'Advanced Feature', 'buddyboss' ),
    'type'  => 'text',
    'conditional' => array(
        'relation' => 'AND', // 'AND' or 'OR'
        'rules' => array(
            array(
                'field'   => 'bb_group_feature_enabled',
                'compare' => '==',
                'value'   => true,
            ),
            array(
                'field'   => 'bb_group_license_tier',
                'compare' => 'in',
                'value'   => array( 'pro', 'plus' ),
            ),
        ),
    ),
) );
```

- **React Conditional Rendering**:
```javascript
function ConditionalField({ field, allFields, allValues }) {
    if (!field.conditional) {
        return <FieldRenderer field={field} />;
    }
    
    const condition = field.conditional;
    const shouldShow = evaluateCondition(condition, allValues);
    
    if (!shouldShow) {
        return null; // Don't render
    }
    
    return <FieldRenderer field={field} />;
}

function evaluateCondition(condition, values) {
    if (condition.relation) {
        // Complex condition with AND/OR
        const results = condition.rules.map(rule => 
            evaluateRule(rule, values)
        );
        
        if (condition.relation === 'AND') {
            return results.every(r => r);
        } else {
            return results.some(r => r);
        }
    } else {
        // Simple condition
        return evaluateRule(condition, values);
    }
}

function evaluateRule(rule, values) {
    const fieldValue = values[rule.field];
    const compare = rule.compare;
    const target = rule.value;
    
    switch (compare) {
        case '==':
        case 'equals':
            return fieldValue == target;
        case '!=':
        case 'not_equals':
            return fieldValue != target;
        case '>':
            return fieldValue > target;
        case '>=':
            return fieldValue >= target;
        case '<':
            return fieldValue < target;
        case '<=':
            return fieldValue <= target;
        case 'in':
            return Array.isArray(target) && target.includes(fieldValue);
        case 'not_in':
            return Array.isArray(target) && !target.includes(fieldValue);
        case 'contains':
            return String(fieldValue).includes(String(target));
        case 'regex':
            return new RegExp(target).test(fieldValue);
        default:
            return true;
    }
}
```

- **Nested Conditions** (field depends on multiple fields):
```php
bb_register_feature_field( 'activity', 'activity_advanced', array(
    'name'  => 'bb_activity_custom_setting',
    'label' => __( 'Custom Setting', 'buddyboss' ),
    'type'  => 'text',
    'conditional' => array(
        'relation' => 'AND',
        'rules' => array(
            array(
                'field'   => 'bb_activity_enabled',
                'compare' => '==',
                'value'   => true,
            ),
            array(
                'field'   => 'bb_activity_type',
                'compare' => '==',
                'value'   => 'custom',
            ),
        ),
    ),
) );
```

## 5. REST API Layer for Features & Settings

The React admin will communicate with the backend via **WordPress REST API** endpoints.

### 5.1 Endpoints

Proposed endpoints:

- **List features**
  - `GET /wp-json/buddyboss/v1/features`
    - Returns array of features with:
      - `id`, `label`, `description`, `icon`, `category`, `status` (`active`/`inactive`), `license_tier`, etc.
      - Counts for filters: `active_count`, `inactive_count`, counts by category.

- **Feature details (sections + fields + navigation)**
  - `GET /wp-json/buddyboss/v1/features/{featureId}`
    - Returns:
      - `feature` metadata.
      - `sections` → each with `id`, `title`, `description`, `nav_group`, `order`, `is_default`.
      - `fields` → each with `name`, `label`, `type`, `value`, `default`, `license_tier`, `pro_only`, `options`, etc.
      - `navigation` → array of navigation items (sections + custom nav items) for the left sidebar, grouped by `nav_group`, sorted by `order`.

- **Update feature settings**
  - `POST /wp-json/buddyboss/v1/features/{featureId}/settings`
    - Accepts payload of `{ fieldName: newValue, ... }`.
    - Validates via defined `sanitize_callback`.
    - Calls `update_option()` or uses registered settings as appropriate.
    - **Validation errors**:
      - Returns `400 Bad Request` with error details if validation fails.
      - Error response format: `{ code: 'validation_error', message: '...', errors: { fieldName: 'Error message' } }`.
      - React UI displays field-level errors next to invalid fields.
    - **Success response**:
      - Returns `200 OK` with updated settings: `{ success: true, settings: { ... } }`.

- **Toggle feature activation** (for component enable/disable)
  - `POST /wp-json/buddyboss/v1/features/{featureId}/toggle`
    - Accepts payload: `{ active: true|false }` or `{ status: 'active'|'inactive' }`.
    - Updates the underlying component option (e.g., `bp-active-components` array).
    - Triggers hooks: `bb_feature_activated_{$featureId}` or `bb_feature_deactivated_{$featureId}`.
    - Returns updated feature status.
    - **Implementation**:
      - Reads `is_active_callback` from registry to determine current state.
      - Updates the option that the callback checks (e.g., adds/removes from `bp-active-components`).
      - Clears relevant caches (transients, object cache).
      - Fires action hooks for other code to react to activation/deactivation.

- **Settings search**
  - `GET /wp-json/buddyboss/v1/settings/search?query=...`
    - Returns matching settings across all features, including:
      - `featureId`, `sectionId`, `fieldName`.
      - `breadcrumb` (e.g., "Activity → Activity Sharing → Allow sharing to Twitter").
      - `route` (`/settings/activity/activity_sharing`).

- **Dashboard endpoints**
  - `GET /wp-json/buddyboss/v1/dashboard/installs`
    - Returns Platform and Pro version information, update status.
    - Response: `{ platform: { version, update_available }, pro: { version, update_available, license_status } }`
  - `GET /wp-json/buddyboss/v1/dashboard/analytics`
    - Returns community analytics data (user counts, active users, retention metrics).
    - Response: `{ total_users, active_users, new_users_this_month, retention_rate, top_groups, recent_activity }`
    - Supports date range: `?start_date=2024-01-01&end_date=2024-01-31`
  - `GET /wp-json/buddyboss/v1/dashboard/scheduled-posts`
    - Returns list of scheduled activity posts.
    - Supports pagination: `?page=1&per_page=10`
  - `GET /wp-json/buddyboss/v1/dashboard/recommendations`
    - Returns recommended plugins/integrations based on current setup.
    - Response: `{ plugins: [...], integrations: [...] }`

- **Settings import/export**
  - `GET /wp-json/buddyboss/v1/settings/export?feature_id=activity&format=json`
    - Exports feature settings as JSON.
    - Optional `feature_id` to export specific feature, or omit for all features.
    - Formats: `json`, `php` (PHP array format).
  - `POST /wp-json/buddyboss/v1/settings/import`
    - Imports settings from JSON/array.
    - Accepts: `{ settings: {...}, feature_id: 'activity', dry_run: false }`
    - Returns: `{ success: true, imported: [...], skipped: [...], errors: [...] }`

- **Settings reset**
  - `POST /wp-json/buddyboss/v1/features/{featureId}/settings/reset`
    - Resets feature settings to defaults.
    - Optional `section_id` to reset specific section.
    - Optional `field_name` to reset specific field.
    - Response: `{ success: true, reset_fields: [...] }`

- **Settings history/audit log**
  - `GET /wp-json/buddyboss/v1/settings/history?feature_id=activity&limit=50`
    - Returns change history for settings.
    - Response: `{ changes: [{ field, old_value, new_value, user, timestamp }] }`
    - Supports pagination and filtering by feature/section/field.

### 5.3 Feature Activation Side Effects

**Problem**: Features may need to perform actions when activated/deactivated (create pages, set up cron jobs, etc.).

**Solution**: Implement activation/deactivation lifecycle hooks:

- **Activation Callbacks**:
```php
bb_register_feature( 'activity', array(
    'label' => __( 'Activity', 'buddyboss' ),
    'on_activate' => function( $feature_id ) {
        // Create Activity page if it doesn't exist
        $page_id = bp_core_get_directory_page_id( 'activity' );
        if ( ! $page_id ) {
            bp_core_create_directory_page( 'activity' );
        }
        
        // Set up cron job for activity cleanup
        if ( ! wp_next_scheduled( 'bp_activity_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'bp_activity_cleanup' );
        }
        
        // Fire action for other code to hook into
        do_action( 'bb_feature_activated', $feature_id );
    },
    'on_deactivate' => function( $feature_id ) {
        // Remove cron job
        wp_clear_scheduled_hook( 'bp_activity_cleanup' );
        
        // Fire action for cleanup
        do_action( 'bb_feature_deactivated', $feature_id );
    },
    // ...
) );
```

- **Registry Implementation**:
```php
class BB_Feature_Registry {
    public function toggle_feature( $feature_id, $active ) {
        $feature = $this->features[ $feature_id ];
        
        if ( $active ) {
            // Check dependencies first
            $validation = $this->can_activate_feature( $feature_id );
            if ( is_wp_error( $validation ) ) {
                return $validation;
            }
            
            // Update option
            $this->update_activation_option( $feature_id, true );
            
            // Run activation callback
            if ( isset( $feature['on_activate'] ) && is_callable( $feature['on_activate'] ) ) {
                call_user_func( $feature['on_activate'], $feature_id );
            }
            
            // Load PHP code
            $this->load_feature_php( $feature_id );
            
            // Fire hooks
            do_action( "bb_feature_activated_{$feature_id}", $feature_id );
            do_action( 'bb_feature_activated', $feature_id );
        } else {
            // Run deactivation callback
            if ( isset( $feature['on_deactivate'] ) && is_callable( $feature['on_deactivate'] ) ) {
                call_user_func( $feature['on_deactivate'], $feature_id );
            }
            
            // Update option
            $this->update_activation_option( $feature_id, false );
            
            // Fire hooks
            do_action( "bb_feature_deactivated_{$feature_id}", $feature_id );
            do_action( 'bb_feature_deactivated', $feature_id );
        }
        
        // Clear caches
        $this->clear_feature_caches( $feature_id );
        
        return true;
    }
}
```

- **Common Side Effects**:
  - **Page Creation**: Create directory pages for components
  - **Cron Jobs**: Schedule/unschedule background tasks
  - **Database Tables**: Create/remove custom tables (if needed)
  - **Default Settings**: Set default option values
  - **Capabilities**: Add/remove custom capabilities
  - **Rewrite Rules**: Flush rewrite rules if needed

### 5.4 Settings Change History / Audit Log

**Problem**: Need to track who changed what settings and when.

**Solution**: Implement settings change history:

- **History Storage**:
```php
class BB_Settings_History {
    public function log_change( $feature_id, $field_name, $old_value, $new_value, $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bb_settings_history';
        
        $wpdb->insert( $table, array(
            'feature_id'  => $feature_id,
            'field_name'  => $field_name,
            'old_value'   => maybe_serialize( $old_value ),
            'new_value'   => maybe_serialize( $new_value ),
            'user_id'     => $user_id,
            'timestamp'   => current_time( 'mysql' ),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ) );
    }
    
    public function get_history( $args = array() ) {
        $defaults = array(
            'feature_id' => null,
            'field_name' => null,
            'user_id'    => null,
            'limit'      => 50,
            'offset'     => 0,
        );
        $args = wp_parse_args( $args, $defaults );
        
        global $wpdb;
        $table = $wpdb->prefix . 'bb_settings_history';
        $where = array( '1=1' );
        
        if ( $args['feature_id'] ) {
            $where[] = $wpdb->prepare( 'feature_id = %s', $args['feature_id'] );
        }
        if ( $args['field_name'] ) {
            $where[] = $wpdb->prepare( 'field_name = %s', $args['field_name'] );
        }
        if ( $args['user_id'] ) {
            $where[] = $wpdb->prepare( 'user_id = %d', $args['user_id'] );
        }
        
        $query = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where );
        $query .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results( $wpdb->prepare( $query, $args['limit'], $args['offset'] ) );
    }
}
```

- **Integration with Settings Update**:
```php
// In REST controller
public function update_settings( $request ) {
    $feature_id = $request->get_param( 'feature_id' );
    $settings = $request->get_json_params();
    $history = new BB_Settings_History();
    
    // Get current values
    $current_settings = $this->get_feature_settings( $feature_id );
    
    // Update and log changes
    foreach ( $settings as $field_name => $new_value ) {
        $old_value = $current_settings[ $field_name ] ?? null;
        
        if ( $old_value !== $new_value ) {
            // Log change
            $history->log_change( $feature_id, $field_name, $old_value, $new_value );
        }
    }
    
    // Save settings
    // ...
}
```

- **Database Schema** (optional table):
```sql
CREATE TABLE IF NOT EXISTS wp_bb_settings_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    feature_id VARCHAR(100) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value LONGTEXT,
    new_value LONGTEXT,
    user_id BIGINT UNSIGNED NOT NULL,
    timestamp DATETIME NOT NULL,
    ip_address VARCHAR(45),
    PRIMARY KEY (id),
    KEY feature_id (feature_id),
    KEY field_name (field_name),
    KEY user_id (user_id),
    KEY timestamp (timestamp)
);
```

- **Settings History Retention Policy**:
```php
class BB_Settings_History {
    // Default retention: 90 days
    private $retention_days = 90;
    
    /**
     * Clean up old history entries based on retention policy.
     * Should be called via WP Cron daily.
     */
    public function cleanup_old_history() {
        global $wpdb;
        $table = $wpdb->prefix . 'bb_settings_history';
        
        // Get retention days from option (allows customization)
        $retention_days = get_option( 'bb_settings_history_retention_days', $this->retention_days );
        
        // Calculate cutoff date
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );
        
        // Delete old entries
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} WHERE timestamp < %s",
            $cutoff_date
        ) );
        
        // Log cleanup
        if ( $deleted > 0 ) {
            error_log( sprintf(
                'BuddyBoss Settings History: Cleaned up %d old entries (older than %d days)',
                $deleted,
                $retention_days
            ) );
        }
        
        return $deleted;
    }
    
    /**
     * Get retention policy setting.
     */
    public function get_retention_days() {
        return get_option( 'bb_settings_history_retention_days', $this->retention_days );
    }
    
    /**
     * Set retention policy.
     */
    public function set_retention_days( $days ) {
        // Minimum 7 days, maximum 365 days
        $days = max( 7, min( 365, absint( $days ) ) );
        update_option( 'bb_settings_history_retention_days', $days );
        return $days;
    }
}

// Schedule daily cleanup
add_action( 'bb_daily_cleanup', array( 'BB_Settings_History', 'cleanup_old_history' ) );
if ( ! wp_next_scheduled( 'bb_daily_cleanup' ) ) {
    wp_schedule_event( time(), 'daily', 'bb_daily_cleanup' );
}
```

**Retention Policy Configuration**:
- Default: 90 days
- Configurable via option: `bb_settings_history_retention_days`
- Minimum: 7 days (to prevent accidental data loss)
- Maximum: 365 days (to prevent unbounded growth)
- Cleanup runs daily via WP Cron
- Can be disabled by setting retention to 0 (not recommended)

### 5.5 REST API Response Standards & Conventions

All BuddyBoss REST API endpoints follow consistent response formats for maintainability, predictability, and alignment with WordPress REST API conventions.

#### 5.5.1 Standard Response Formats

**Success Response (Non-Paginated)**:
```json
{
  "success": true,
  "data": {
    /* Actual response data */
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0"
  }
}
```

**Fields**:
- `success` (boolean) – Always `true` for successful responses
- `data` (object|array) – The actual response payload
- `meta` (object) – Metadata about the response (optional)
  - `timestamp` – ISO 8601 timestamp of response
  - `version` – API version used
  - Additional metadata as needed (execution time, cache status, etc.)

**Success Response (Paginated)**:

For endpoints returning lists (features, activity, groups, etc.):
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "..." },
    { "id": 2, "name": "..." }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  },
  "links": {
    "self": "/buddyboss/v1/features?page=1&per_page=20",
    "first": "/buddyboss/v1/features?page=1&per_page=20",
    "prev": null,
    "next": "/buddyboss/v1/features?page=2&per_page=20",
    "last": "/buddyboss/v1/features?page=8&per_page=20"
  }
}
```

**HTTP Headers**:
- `X-WP-Total: 150`
- `X-WP-TotalPages: 8`
- `Link: </buddyboss/v1/features?page=2&per_page=20>; rel="next"`

**Pagination Fields**:
- `page` (integer) – Current page number (1-indexed)
- `per_page` (integer) – Items per page
- `total` (integer) – Total number of items across all pages
- `total_pages` (integer) – Total number of pages

**Links Object (HATEOAS)**:
- `self` – Current page URL
- `first` – First page URL
- `prev` – Previous page URL (`null` if on first page)
- `next` – Next page URL (`null` if on last page)
- `last` – Last page URL

**Error Response**:
```json
{
  "success": false,
  "code": "validation_error",
  "message": "Validation failed for one or more fields.",
  "data": {
    "status": 400,
    "errors": {
      "field_name": "Field-specific error message",
      "another_field": "Another error message"
    },
    "details": {
      /* Optional additional error context */
    }
  }
}
```

**HTTP Status Codes**:
- `200` – Success
- `201` – Created (for POST requests that create resources)
- `400` – Bad Request (validation error, malformed request)
- `401` – Unauthorized (not logged in)
- `403` – Forbidden (logged in but insufficient permissions)
- `404` – Not Found (resource doesn't exist)
- `409` – Conflict (duplicate resource, state conflict)
- `429` – Too Many Requests (rate limit exceeded)
- `500` – Internal Server Error (unexpected error)

**Error Codes**:
- `validation_error` – Field validation failed
- `missing_dependencies` – Required feature dependencies not active
- `license_required` – Feature requires valid license
- `feature_not_found` – Feature ID doesn't exist
- `rest_forbidden` – User lacks required capability
- `rest_invalid_nonce` – Nonce verification failed
- `rest_too_many_requests` – Rate limit exceeded
- `rest_no_route` – Endpoint doesn't exist

#### 5.5.2 Query Parameter Conventions

All list endpoints support these standard query parameters:

**Pagination**:
- `page` (integer, default: 1) – Page number
- `per_page` (integer, default: 20, max: 100) – Items per page
- `offset` (integer) – Alternative to page, number of items to skip

**Sorting**:
- `orderby` (string, default: varies by endpoint) – Field to sort by
  - Common values: `id`, `name`, `date`, `title`, `status`
- `order` (string, default: `asc`) – Sort direction
  - Values: `asc` (ascending), `desc` (descending)

**Searching**:
- `search` (string) – Search term to filter results
  - Searches relevant fields (name, title, description, etc.)
  - Minimum 2 characters

**Filtering**:
- Endpoint-specific filters as query parameters
- Use array syntax for multiple values: `status[]=active&status[]=pending`
- Examples:
  - `status` – Filter by status (`active`, `inactive`, `pending`, etc.)
  - `type` – Filter by type (`public`, `private`, `hidden`, etc.)
  - `category` – Filter by category
  - `license_tier` – Filter by license tier (`free`, `pro`, `plus`)

**Field Selection (Partial Responses)**:
- `_fields` (comma-separated) – Return only specified fields
  - Example: `?_fields=id,name,status`
  - Reduces response size and improves performance

**Embedding Related Resources**:
- `_embed` (boolean) – Include related resources in response
  - Example: `/features/activity?_embed=1` includes sections and fields
  - Reduces number of API requests needed

#### 5.5.3 PHP Implementation Classes

**Response Formatter Class**:
```php
<?php
/**
 * Standard REST API response formatter for BuddyBoss.
 *
 * Ensures consistent response formats across all endpoints.
 *
 * @since 3.0.0
 */
class BB_REST_Response {

    /**
     * Create a successful response.
     *
     * @param mixed $data Response data.
     * @param array $meta Optional metadata.
     * @return WP_REST_Response
     */
    public static function success( $data, $meta = array() ) {
        $response_data = array(
            'success' => true,
            'data'    => $data,
        );

        // Add metadata if provided
        if ( ! empty( $meta ) ) {
            $response_data['meta'] = array_merge(
                array(
                    'timestamp' => current_time( 'c' ), // ISO 8601
                    'version'   => '1.0',
                ),
                $meta
            );
        }

        return rest_ensure_response( $response_data );
    }

    /**
     * Create a paginated response.
     *
     * @param array $data    Array of items for current page.
     * @param int   $total   Total number of items across all pages.
     * @param int   $page    Current page number.
     * @param int   $per_page Items per page.
     * @param array $args    Optional additional args (base_url, query_params).
     * @return WP_REST_Response
     */
    public static function paginated( $data, $total, $page, $per_page, $args = array() ) {
        $total_pages = (int) ceil( $total / $per_page );

        $response_data = array(
            'success'    => true,
            'data'       => $data,
            'pagination' => array(
                'page'        => (int) $page,
                'per_page'    => (int) $per_page,
                'total'       => (int) $total,
                'total_pages' => $total_pages,
            ),
        );

        // Generate HATEOAS links
        if ( ! empty( $args['base_url'] ) ) {
            $response_data['links'] = self::generate_pagination_links(
                $args['base_url'],
                $page,
                $per_page,
                $total_pages,
                $args['query_params'] ?? array()
            );
        }

        $response = rest_ensure_response( $response_data );

        // Add standard WordPress headers
        $response->header( 'X-WP-Total', (int) $total );
        $response->header( 'X-WP-TotalPages', $total_pages );

        // Add Link header for next/prev
        if ( $page < $total_pages ) {
            $next_url = add_query_arg( 'page', $page + 1, $args['base_url'] ?? '' );
            $response->header( 'Link', sprintf( '<%s>; rel="next"', $next_url ) );
        }

        return $response;
    }

    /**
     * Create an error response.
     *
     * @param string $code    Error code.
     * @param string $message Error message.
     * @param int    $status  HTTP status code.
     * @param array  $errors  Field-specific errors.
     * @param array  $details Additional error details.
     * @return WP_Error
     */
    public static function error( $code, $message, $status = 400, $errors = array(), $details = array() ) {
        $error_data = array(
            'status' => $status,
        );

        if ( ! empty( $errors ) ) {
            $error_data['errors'] = $errors;
        }

        if ( ! empty( $details ) ) {
            $error_data['details'] = $details;
        }

        return new WP_Error( $code, $message, $error_data );
    }

    /**
     * Generate pagination links (HATEOAS).
     *
     * @param string $base_url    Base URL for the endpoint.
     * @param int    $page        Current page.
     * @param int    $per_page    Items per page.
     * @param int    $total_pages Total pages.
     * @param array  $query_params Additional query parameters.
     * @return array Links array.
     */
    private static function generate_pagination_links( $base_url, $page, $per_page, $total_pages, $query_params = array() ) {
        $links = array();

        $base_params = array_merge(
            $query_params,
            array( 'per_page' => $per_page )
        );

        // Self link
        $links['self'] = add_query_arg(
            array_merge( $base_params, array( 'page' => $page ) ),
            $base_url
        );

        // First link
        $links['first'] = add_query_arg(
            array_merge( $base_params, array( 'page' => 1 ) ),
            $base_url
        );

        // Previous link
        $links['prev'] = $page > 1
            ? add_query_arg( array_merge( $base_params, array( 'page' => $page - 1 ) ), $base_url )
            : null;

        // Next link
        $links['next'] = $page < $total_pages
            ? add_query_arg( array_merge( $base_params, array( 'page' => $page + 1 ) ), $base_url )
            : null;

        // Last link
        $links['last'] = add_query_arg(
            array_merge( $base_params, array( 'page' => $total_pages ) ),
            $base_url
        );

        return $links;
    }

    /**
     * Validate pagination parameters.
     *
     * @param WP_REST_Request $request Request object.
     * @return array Validated pagination params.
     */
    public static function validate_pagination_params( $request ) {
        $page     = $request->get_param( 'page' ) ?? 1;
        $per_page = $request->get_param( 'per_page' ) ?? 20;

        // Sanitize and validate
        $page     = max( 1, absint( $page ) );
        $per_page = max( 1, min( 100, absint( $per_page ) ) ); // Max 100 items per page

        return array(
            'page'     => $page,
            'per_page' => $per_page,
            'offset'   => ( $page - 1 ) * $per_page,
        );
    }

    /**
     * Format validation errors for response.
     *
     * @param array $validation_errors Array of field => error message.
     * @return WP_Error
     */
    public static function validation_error( $validation_errors ) {
        return self::error(
            'validation_error',
            __( 'Validation failed for one or more fields.', 'buddyboss' ),
            400,
            $validation_errors
        );
    }

    /**
     * Format permission error.
     *
     * @param string $message Optional custom message.
     * @return WP_Error
     */
    public static function permission_error( $message = '' ) {
        if ( empty( $message ) ) {
            $message = __( 'Sorry, you are not allowed to do that.', 'buddyboss' );
        }

        return self::error(
            'rest_forbidden',
            $message,
            403
        );
    }

    /**
     * Format not found error.
     *
     * @param string $resource_type Type of resource (e.g., 'feature', 'group').
     * @param mixed  $identifier    Resource identifier.
     * @return WP_Error
     */
    public static function not_found( $resource_type, $identifier ) {
        return self::error(
            'rest_not_found',
            sprintf(
                __( '%s with ID "%s" not found.', 'buddyboss' ),
                ucfirst( $resource_type ),
                $identifier
            ),
            404
        );
    }
}
```

#### 5.5.4 Usage Examples

**Example 1: Simple GET Endpoint (Non-Paginated)**:
```php
/**
 * Get single feature details.
 *
 * GET /buddyboss/v1/features/{id}
 */
class BB_REST_Features_Controller extends WP_REST_Controller {

    public function get_item( $request ) {
        $feature_id = $request->get_param( 'id' );

        // Get feature from registry
        $feature = $this->registry->get_feature( $feature_id );

        if ( ! $feature ) {
            return BB_REST_Response::not_found( 'feature', $feature_id );
        }

        // Check if feature is available (license check)
        if ( ! $this->registry->is_feature_available( $feature_id ) ) {
            return BB_REST_Response::error(
                'license_required',
                sprintf(
                    __( 'Feature "%s" requires a valid license.', 'buddyboss' ),
                    $feature['label']
                ),
                403
            );
        }

        // Return success response
        return BB_REST_Response::success( $feature );
    }
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": "activity",
    "label": "Activity",
    "description": "Enable activity feeds...",
    "status": "active",
    "sections": [...],
    "fields": [...]
  }
}
```

**Example 2: Paginated GET Endpoint**:
```php
/**
 * Get all features (paginated).
 *
 * GET /buddyboss/v1/features?page=1&per_page=20
 */
class BB_REST_Features_Controller extends WP_REST_Controller {

    public function get_items( $request ) {
        // Validate pagination params
        $pagination = BB_REST_Response::validate_pagination_params( $request );

        // Get filters
        $status   = $request->get_param( 'status' );   // 'active', 'inactive'
        $category = $request->get_param( 'category' ); // 'community', 'add-ons'
        $search   = $request->get_param( 'search' );

        // Get all features from registry
        $all_features = $this->registry->get_features();

        // Apply filters
        $filtered = $this->filter_features( $all_features, $status, $category, $search );

        // Get total count
        $total = count( $filtered );

        // Apply pagination
        $paged_features = array_slice(
            $filtered,
            $pagination['offset'],
            $pagination['per_page']
        );

        // Return paginated response
        return BB_REST_Response::paginated(
            $paged_features,
            $total,
            $pagination['page'],
            $pagination['per_page'],
            array(
                'base_url'     => rest_url( 'buddyboss/v1/features' ),
                'query_params' => array_filter( array(
                    'status'   => $status,
                    'category' => $category,
                    'search'   => $search,
                ) ),
            )
        );
    }

    private function filter_features( $features, $status, $category, $search ) {
        $filtered = $features;

        // Filter by status
        if ( $status ) {
            $filtered = array_filter( $filtered, function( $feature ) use ( $status ) {
                return $feature['status'] === $status;
            } );
        }

        // Filter by category
        if ( $category ) {
            $filtered = array_filter( $filtered, function( $feature ) use ( $category ) {
                return $feature['category'] === $category;
            } );
        }

        // Filter by search
        if ( $search && strlen( $search ) >= 2 ) {
            $search_lower = strtolower( $search );
            $filtered = array_filter( $filtered, function( $feature ) use ( $search_lower ) {
                return stripos( $feature['label'], $search_lower ) !== false
                    || stripos( $feature['description'], $search_lower ) !== false;
            } );
        }

        return array_values( $filtered ); // Re-index array
    }
}
```

**Response**:
```json
{
  "success": true,
  "data": [
    { "id": "activity", "label": "Activity", ... },
    { "id": "groups", "label": "Groups", ... }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 45,
    "total_pages": 3
  },
  "links": {
    "self": "/buddyboss/v1/features?page=1&per_page=20",
    "first": "/buddyboss/v1/features?page=1&per_page=20",
    "prev": null,
    "next": "/buddyboss/v1/features?page=2&per_page=20",
    "last": "/buddyboss/v1/features?page=3&per_page=20"
  }
}
```

**Example 3: POST Endpoint with Validation**:
```php
/**
 * Update feature settings.
 *
 * POST /buddyboss/v1/features/{id}/settings
 */
class BB_REST_Features_Controller extends WP_REST_Controller {

    public function update_item( $request ) {
        $feature_id = $request->get_param( 'id' );
        $settings   = $request->get_json_params();

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            return BB_REST_Response::permission_error();
        }

        // Verify nonce
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return BB_REST_Response::error(
                'rest_invalid_nonce',
                __( 'Invalid security token.', 'buddyboss' ),
                403
            );
        }

        // Check feature exists
        $feature = $this->registry->get_feature( $feature_id );
        if ( ! $feature ) {
            return BB_REST_Response::not_found( 'feature', $feature_id );
        }

        // Validate each field
        $validation_errors = array();
        $updated_fields    = array();

        foreach ( $settings as $field_name => $value ) {
            $field = $this->registry->get_field( $feature_id, $field_name );

            if ( ! $field ) {
                $validation_errors[ $field_name ] = sprintf(
                    __( 'Field "%s" does not exist.', 'buddyboss' ),
                    $field_name
                );
                continue;
            }

            // Sanitize
            if ( isset( $field['sanitize_callback'] ) ) {
                $value = call_user_func( $field['sanitize_callback'], $value );
            }

            // Validate
            $validation = $this->validate_field( $field, $value, $settings );
            if ( is_wp_error( $validation ) ) {
                $validation_errors[ $field_name ] = $validation->get_error_message();
                continue;
            }

            // Update option
            update_option( $field_name, $value );
            $updated_fields[ $field_name ] = $value;

            // Log change to history
            $this->history->log_change(
                $feature_id,
                $field_name,
                get_option( $field_name ),
                $value
            );
        }

        // If validation errors, return error response
        if ( ! empty( $validation_errors ) ) {
            return BB_REST_Response::validation_error( $validation_errors );
        }

        // Clear caches
        $this->registry->clear_feature_caches( $feature_id );

        // Fire action hook
        do_action( 'bb_feature_settings_updated', $feature_id, $updated_fields );

        // Return success response
        return BB_REST_Response::success(
            array(
                'updated' => $updated_fields,
            ),
            array(
                'execution_time' => microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'],
            )
        );
    }
}
```

**Success Response**:
```json
{
  "success": true,
  "data": {
    "updated": {
      "_bp_enable_activity_edit": true,
      "_bp_activity_edit_timeout": 300
    }
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0",
    "execution_time": 0.045
  }
}
```

**Error Response**:
```json
{
  "success": false,
  "code": "validation_error",
  "message": "Validation failed for one or more fields.",
  "data": {
    "status": 400,
    "errors": {
      "_bp_activity_edit_timeout": "Value must be between 60 and 3600 seconds.",
      "_bp_activity_invalid_field": "Field \"_bp_activity_invalid_field\" does not exist."
    }
  }
}
```

**Example 4: Endpoint with Rate Limiting**:
```php
/**
 * Search settings endpoint.
 *
 * GET /buddyboss/v1/settings/search?query=activity
 */
class BB_REST_Settings_Search_Controller extends WP_REST_Controller {

    public function get_items( $request ) {
        // Check rate limit
        $rate_limiter = new BB_REST_Rate_Limiter();
        $rate_check   = $rate_limiter->check_rate_limit( 'settings_search', 60 );

        if ( is_wp_error( $rate_check ) ) {
            return $rate_check; // Return 429 error
        }

        $query = $request->get_param( 'query' );

        // Validate query
        if ( empty( $query ) || strlen( $query ) < 2 ) {
            return BB_REST_Response::error(
                'invalid_query',
                __( 'Search query must be at least 2 characters.', 'buddyboss' ),
                400
            );
        }

        // Search index
        $results = $this->search_settings( $query );

        // Return results
        return BB_REST_Response::success(
            array(
                'query'   => $query,
                'results' => $results,
                'count'   => count( $results ),
            ),
            array(
                'cached' => false,
            )
        );
    }
}
```

#### 5.5.5 Endpoint Registration Schema

When registering REST endpoints, use this schema format:
```php
register_rest_route( 'buddyboss/v1', '/features', array(
    array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' ),
        'args'                => array(
            'page' => array(
                'description'       => __( 'Page number for pagination.', 'buddyboss' ),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __( 'Number of items per page.', 'buddyboss' ),
                'type'              => 'integer',
                'default'           => 20,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
            'status' => array(
                'description'       => __( 'Filter by feature status.', 'buddyboss' ),
                'type'              => 'string',
                'enum'              => array( 'active', 'inactive' ),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'search' => array(
                'description'       => __( 'Search term to filter results.', 'buddyboss' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ),
    'schema' => array( $this, 'get_public_item_schema' ),
) );
```

#### 5.5.6 Backward Compatibility

For existing endpoints that don't follow this format:

1. **Gradual Migration**: Update endpoints one at a time during Phase 1-2
2. **Response Wrapper**: Create a wrapper that converts old format to new:
```php
/**
 * Wrap legacy response in new format.
 *
 * @param mixed $legacy_response Old response format.
 * @return WP_REST_Response New format.
 */
function bb_wrap_legacy_response( $legacy_response ) {
    if ( is_wp_error( $legacy_response ) ) {
        return $legacy_response; // Already proper format
    }

    return BB_REST_Response::success( $legacy_response );
}
```
3. **Version Header**: Add `X-BB-API-Version: 1.0` header to identify format version

#### 5.5.7 Testing Checklist

For each REST endpoint, verify:

- [ ] Returns standard response envelope format
- [ ] Includes proper HTTP status codes
- [ ] Paginated endpoints include pagination metadata
- [ ] Paginated endpoints include `X-WP-Total` and `X-WP-TotalPages` headers
- [ ] Error responses include error code, message, and status
- [ ] Validation errors include field-level error details
- [ ] Rate limiting works (test with rapid requests)
- [ ] Nonce verification works
- [ ] Permission checks work for different user roles
- [ ] Query parameters are sanitized and validated
- [ ] Response includes HATEOAS links (for paginated endpoints)
- [ ] Schema validation passes (use WP REST API schema validator)

#### 5.5.8 Performance Considerations

**Response Caching**:
```php
// Cache expensive queries
$cache_key = 'bb_features_list_' . md5( serialize( $args ) );
$cached    = get_transient( $cache_key );

if ( $cached !== false ) {
    return BB_REST_Response::success(
        $cached,
        array( 'cached' => true )
    );
}

$features = $this->registry->get_features( $args );
set_transient( $cache_key, $features, HOUR_IN_SECONDS );

return BB_REST_Response::success(
    $features,
    array( 'cached' => false )
);
```

**Partial Responses (_fields parameter)**:
```php
// Support _fields parameter to reduce response size
$fields = $request->get_param( '_fields' );
if ( $fields ) {
    $fields_array = explode( ',', $fields );
    $data = array_intersect_key( $data, array_flip( $fields_array ) );
}
```

### 5.6 Implementation Details

- REST controllers:
  - Implement using standard `WP_REST_Controller` patterns.
  - Enforce capabilities (`current_user_can( 'manage_options' )` or appropriate caps).
  - Load data directly from the Feature Registry.
  - Namespace: `/buddyboss/v1/` (allows for future versioning: `/buddyboss/v2/`, etc.).

- **API Versioning**:
  - Current version: `v1` (namespace: `buddyboss/v1`).
  - Future versions can use `v2`, `v3`, etc.
  - Maintain backward compatibility for at least one major version.
  - Document breaking changes in API version updates.

- **Cache Management**:
  - Feature Registry data is cached in memory during request.
  - Use WordPress transients for expensive queries (e.g., feature counts, search index).
  - Clear caches when:
    - Features are activated/deactivated.
    - Settings are updated.
    - New features are registered (via `bb_register_features` hook).
  - Cache keys: `bb_features_list`, `bb_feature_{$id}`, `bb_settings_search_index`.
  - Cache invalidation: Use `wp_cache_delete()` and `delete_transient()` when data changes.

- **Multisite Considerations**:
  - Feature Registry works per-site (not network-wide).
  - Each site can have different features active.
  - REST endpoints respect current site context.
  - Network admin can have separate feature management (if needed).
  - Options are stored per-site (not network-wide).
  - Transients are site-specific (use `get_transient()`, `set_transient()` which are site-aware).

- **Indexing for search**:
  - Either build search on the fly (walk registry and match text).
  - Or precompute when registry is built (simple in-memory index).
  - Cache search index in transient (refresh on feature/setting changes).
  - Search index includes: feature names, section titles, field labels, descriptions, option names.

- **Icon Handling & Custom Icon Support**:
  - **Icon Types Supported**:
    1. **Dashicons** (WordPress core icons):
       - Use Dashicon slug, React renders via `wp.components` `Icon` component: `<Icon icon="dashicons-groups" />`.
       - No registration needed - WordPress core provides these.
    2. **Custom SVG Icons**:
       - **SVG URL**: Provide full URL to SVG file (e.g., `'https://example.com/icons/my-icon.svg'`).
       - **SVG Data URI**: Provide data URI (e.g., `'data:image/svg+xml;base64,...'`).
       - **Local SVG**: Path relative to plugin directory (e.g., `'assets/icons/my-icon.svg'`).
       - React renders as `<img>` or inline SVG.
    3. **Image Icons** (PNG, JPG, etc.):
       - Provide full URL to image file.
       - React renders as `<img src={iconUrl} alt={label} />`.
    4. **Custom React Icon Components**:
       - Register a React component that renders the icon.
       - Component is lazy-loaded when needed.
    5. **Font Icons** (Icon Fonts):
       - Support for icon font classes (e.g., `'bb-icons-rl-groups'`).
       - React renders as `<span className={iconClass} />`.
  
  - **Icon Registry API**:
    - **Function**: `bb_register_icon( string $icon_id, array $args )`
    - **When to use**: Register custom icons that can be reused across features.
    - See section 4.0 for complete icon registry documentation.
  
  - **REST API Icon Response**:
    - REST API returns icon data in feature responses:
```json
{
  "icon": {
    "type": "svg",
    "url": "https://example.com/icons/feature.svg",
    "id": "my-plugin-icon"
  }
}
```
    - Or for Dashicons:
```json
{
  "icon": {
    "type": "dashicon",
    "slug": "dashicons-groups"
  }
}
```

- **License Validation Integration**:
  - Feature Registry checks `is_available_callback()` before showing features.
  - REST API filters out unavailable features (or marks them as `available: false`).
  - React UI:
    - Shows "Upgrade Pro" badge on Pro/Plus features if license invalid.
    - Disables toggle for unavailable features.
    - Shows upgrade message when clicking unavailable features.
  - License checks use existing BuddyBoss license functions:
    - `bb_pro_is_license_valid()` for Platform Pro features.
    - `bb_gm_is_license_valid()` for Gamification features.
    - Platform core features always available (Free tier).
  - **License Check Caching**:
    - Cache license validation results in transient (5 minute TTL).
    - Cache key: `bb_license_check_{$feature_id}_{$site_id}`.
    - Clear cache on license update or plugin activation/deactivation.
    - Implementation:
```php
function bb_is_feature_available( $feature_id ) {
    $cache_key = 'bb_license_check_' . $feature_id . '_' . get_current_blog_id();
    $cached = get_transient( $cache_key );
    
    if ( false !== $cached ) {
        return $cached;
    }
    
    $feature = bb_get_feature( $feature_id );
    if ( ! $feature || ! isset( $feature['is_available_callback'] ) ) {
        return true; // Default to available
    }
    
    $is_available = call_user_func( $feature['is_available_callback'] );
    set_transient( $cache_key, $is_available, 5 * MINUTE_IN_SECONDS );
    
    return $is_available;
}
```

## 6. React Admin Shell (WordPress React)

The new admin experience is a **single-page-style React application** embedded in the BuddyBoss admin menu. It reuses the existing WordPress admin chrome (black top bar + left admin menu) and adds a BuddyBoss-specific header, dashboard, settings grid, and feature-detail screens, all powered by WordPress React.

### 6.1 Mounting Points & Bundles

- **Main shell mount**
  - Update the BuddyBoss main menu callback (currently a PHP-rendered screen) to:
    - Output a root element such as `<div id="bb-admin-app"></div>`.
    - Enqueue:
      - `bb-admin-shell.js` + `bb-admin-shell.css` built with existing tooling.
    - Asset file `bb-admin-shell.asset.php` should specify:
      - Dependencies: `array( 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' )` and, if required by the build, `'react'`, `'react-dom'` as in ReadyLaunch.
- **Other menu entries using the shell**
  - For consistency and to avoid duplicate apps, other BuddyBoss admin pages should be routed through the same shell:
    - `page=bp-components` (Components → now feature toggles).
    - `page=bp-settings` (Settings).
    - `page=bp-integrations` (Integrations).
  - Their PHP callbacks:
    - Either:
      - Render the same `<div id="bb-admin-app"></div>` and enqueue the shell script, or
      - Redirect to the main BuddyBoss shell route (e.g. `admin.php?page=buddyboss-platform#/settings`).
- **Code-splitting**
  - `bb-admin-shell.js`:
    - Contains the minimal shell (layout, routing, header, Settings grid).
    - Dynamically `import()`s per-feature bundles when needed:
      - `bb-admin-feature-activity.js`
      - `bb-admin-feature-groups.js`
      - etc.
  - Per-feature bundles are only requested when:
    - The route enters that feature’s area.
    - The corresponding Feature Registry entry reports `is_active() === true`.

### 6.2 Shell Layout (Matches Dashboard / Settings Designs)

- **Top bar (`top-bar`)**
  - Reuse the existing WP admin black bar (already present).
  - No additional React is needed; we simply render beneath it.

- **BuddyBoss top header (`topHeader`)**
  - Rendered inside the shell using a React component that mirrors the Figma `topHeader`:
    - Left: BuddyBoss logo (`BbLogo`).
    - Center: global **settings search** input (“Search for settings…”).
    - Right: icons for **notifications** and **documentation/help**.
  - The header is present across all shell routes (Dashboard, Settings, Activity/Groups admin screens).

- **Content layout**
  - For the **Dashboard** route:
    - Two-column layout matching Figma design:
      - **Left column**:
        - Welcome panel with YouTube embed (getting started video)
        - Quick create actions (Create Group, Create Activity, etc.)
        - BuddyBoss Installs card (Platform and Pro versions, update status)
        - Community Analytics cards (user counts, active users, retention metrics)
        - Scheduled Posts list (from WordPress posts API)
        - Recent Blog Posts list (from WordPress posts API)
        - "Optimize your Community further" section (recommended plugins/integrations)
      - **Right column**:
        - Setup Guide card (links to key setup tasks)
        - Quick Links card (common admin links)
        - Upgrade to BuddyBoss Plus card (if on Pro license)
        - Integrations card (quick access to integration settings)
        - Social links (Twitter, Facebook, etc.)
    - This screen is implemented as a React component `DashboardScreen` and uses REST endpoints or existing WP APIs for real data where available.
    - **REST endpoints for Dashboard**:
      - `GET /buddyboss/v1/dashboard/installs` - Platform/Pro version info
      - `GET /buddyboss/v1/dashboard/analytics` - Community analytics data
      - `GET /buddyboss/v1/dashboard/scheduled-posts` - Scheduled posts list
      - `GET /buddyboss/v1/dashboard/recommendations` - Recommended plugins/integrations
    - **Data sources**:
      - Use existing WordPress REST API for posts (`/wp/v2/posts`)
      - Use existing BuddyBoss functions for analytics (if available)
      - Cache expensive queries (analytics) using WordPress transients
  - For the **Settings** route:
    - Full-width feature grid, as described in section 7, with filter bar at top.

### 6.3 Routing Strategy

- Use a lightweight internal router inside the React app (either a simple stateful router or `@wordpress/router` if acceptable).
- **Canonical routes (hash or query-based)**
  - We can use either:
    - Hash-style paths, e.g. `admin.php?page=buddyboss-platform#/dashboard`, or
    - Query param paths, e.g. `admin.php?page=buddyboss-platform&view=dashboard`.
  - Hash routing is preferred to avoid interfering with WordPress admin's own routing logic.
- **Route map**
  - `/dashboard` – default when clicking **BuddyBoss** in left menu.
  - `/settings` – Settings feature list (grid view).
  - `/settings/:featureId` – Feature settings overview (e.g. `/settings/activity`, `/settings/groups`).
  - `/settings/:featureId/:sectionId` – Deep-linked section (e.g. `/settings/groups/access-control`).
  - `/activity/all` – All Activity admin list (React version of `bp-activity` admin screen).
  - `/groups/all` – All Groups list.
  - `/groups/create` – Create Group admin.
  - `/groups/:id/edit` – Edit Group.
  - `/groups/types` – Group Types list view.
  - `/groups/types/:id` – Add/Edit Group Type.
  - `/groups/navigation` – Group Navigation designer.
  - Additional routes can be added for other features as they are migrated.

### 6.3.1 Legacy URL Compatibility & Deep Linking

**Critical**: Maintain backward compatibility with existing settings URLs.

- **Legacy URL patterns to support**:
  - `?page=bp-settings&tab=bp-activity` → Maps to `/settings/activity`
  - `?page=bp-settings&tab=bp-groups` → Maps to `/settings/groups`
  - `?page=bp-settings&tab=bp-messages` → Maps to `/settings/messages`
  - `?page=bp-activity` → Maps to `/activity/all`
  - `?page=bp-groups` → Maps to `/groups/all`
  - `?page=bp-groups&gid={id}&action=edit` → Maps to `/groups/{id}/edit`
  - Any other existing `?page=...&tab=...` patterns.

- **URL mapping strategy**:
  - **On React app mount**:
    - Check for `?page` and `?tab` query parameters in URL.
    - Map legacy patterns to new React routes using a mapping table:
```php
// PHP-side mapping (can be exposed via REST or inline script)
$legacy_url_map = array(
    'bp-settings' => array(
        'bp-activity' => '/settings/activity',
        'bp-groups' => '/settings/groups',
        'bp-messages' => '/settings/messages',
        // ... etc
    ),
    'bp-activity' => '/activity/all',
    'bp-groups' => '/groups/all',
);
```
  - **React-side URL detection**:
    - On mount, read `window.location.search` for `?page` and `?tab` params.
    - If found, map to React route and navigate.
    - Update browser history to use new route format (optional: keep query params for compatibility).
  - **Hash-based routing with query param support**:
    - Use hash routing for internal navigation: `#/settings/activity`
    - But also support query params for legacy URLs: `?page=bp-settings&tab=bp-activity`
    - React router can handle both simultaneously.

- **Implementation approach**:
  - **Option A: Query param detection + redirect** (Recommended):
    - On mount, if `?page=bp-settings&tab=bp-activity` detected:
      - Immediately navigate to `#/settings/activity` (or update route state).
      - Optionally update URL to clean format (or keep both for compatibility).
    - **Example React code**:
```javascript
// In main App component or router setup
useEffect(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const page = urlParams.get('page');
  const tab = urlParams.get('tab');
  const section = urlParams.get('section');
  const field = urlParams.get('field');
  
  // Legacy URL mapping
  if (page === 'bp-settings' && tab) {
    const tabMap = {
      'bp-activity': 'activity',
      'bp-groups': 'groups',
      'bp-messages': 'messages',
      // ... etc
    };
    const featureId = tabMap[tab] || tab.replace('bp-', '');
    let route = `/settings/${featureId}`;
    
    if (section) {
      route += `/${section}`;
    }
    
    // Navigate to React route
    navigate(route);
    
    // Optionally update URL to clean format
    if (window.history.replaceState) {
      const newUrl = window.location.pathname + window.location.search.replace(/[?&]page=[^&]*/, '').replace(/[?&]tab=[^&]*/, '').replace(/[?&]section=[^&]*/, '').replace(/[?&]field=[^&]*/, '') + `#${route}`;
      window.history.replaceState({}, '', newUrl);
    }
    
    // Highlight field if specified
    if (field) {
      // Store field ID for highlighting after render
      setHighlightField(field);
    }
  } else if (page === 'bp-activity') {
    navigate('/activity/all');
  } else if (page === 'bp-groups') {
    const gid = urlParams.get('gid');
    const action = urlParams.get('action');
    if (gid && action === 'edit') {
      navigate(`/groups/${gid}/edit`);
    } else {
      navigate('/groups/all');
    }
  }
}, []);
```
  - **Option B: Dual routing support**:
    - React router listens to both hash changes AND query param changes.
    - Route resolver checks query params first, then hash.
    - Both formats work simultaneously.
  - **Recommended: Option A with URL cleanup**:
    - Detect legacy URLs on mount.
    - Navigate to React route.
    - Update URL to clean format (hash-based) for better UX.
    - Keep old URL in history for back button compatibility.

- **Deep linking within settings**:
  - Support section-level deep links:
    - `?page=bp-settings&tab=bp-activity&section=activity_comments` → `/settings/activity/activity_comments`
  - Support field-level deep links (for search results):
    - `?page=bp-settings&tab=bp-activity&section=activity_comments&field=_bp_enable_activity_edit` → `/settings/activity/activity_comments` (with field highlighted)

- **Menu callback updates**:
  - Update `add_submenu_page()` callbacks for Settings:
    - Old: `'bp_core_admin_settings'` callback renders PHP page.
    - New: Callback renders React root `<div id="bb-admin-app"></div>`.
    - React app detects `?page=bp-settings` and routes accordingly.
  - For specific tabs:
    - `?page=bp-settings&tab=bp-activity` → React app routes to `/settings/activity`.
    - `?page=bp-settings&tab=bp-groups` → React app routes to `/settings/groups`.

- **Backward compatibility guarantees**:
  - ✅ All existing bookmarks continue to work.
  - ✅ Direct links to settings tabs continue to work.
  - ✅ Third-party plugins linking to settings tabs continue to work.
  - ✅ Browser back/forward buttons work correctly.
  - ✅ URL sharing works (both old and new formats).

- **Entry behavior**
  - Clicking **BuddyBoss** top-level menu:
    - Opens main shell at `/dashboard` (or `?page=buddyboss-platform`).
  - Clicking **BuddyBoss → Settings**:
    - Opens `/settings` (or `?page=bp-settings`).
  - Clicking **BuddyBoss → Components**:
    - Opens `/settings?tab=components` or a filtered `/settings` view.
  - Clicking **BuddyBoss → Integrations**:
    - Opens `/settings?tab=integrations` or a filtered `/settings` view.
  - **Legacy URLs**:
    - `?page=bp-settings&tab=bp-activity` automatically routes to `/settings/activity`.
    - `?page=bp-activity` automatically routes to `/activity/all`.
    - `?page=bp-groups` automatically routes to `/groups/all`.

### 6.4 Data Flow for Dashboard & Settings Listing

- **Dashboard**
  - Uses a dedicated REST endpoint or a small set of endpoints to populate:
    - BuddyBoss installs: versions & statuses for Platform and Pro.
    - Analytics cards: user counts, active users, retention, etc. (can be incremental and use existing analytics if present).
    - Scheduled posts: from WP posts API.
    - Recent blog posts: from WP posts API.
    - Recommended plugins/integrations: static or dynamic list.
- **Settings feature listing**
  - Uses `GET /buddyboss/v1/features` to populate:
    - Feature cards grouped as:
      - “BuddyBoss Community Settings”
      - “BuddyBoss Add-ons”
      - “BuddyBoss Integrations”
    - Filter counts for:
      - All, Active, Inactive.
      - Groups/categories (e.g. Community, Add-ons, Integrations).
  - Toggles on cards:
    - Call `POST /buddyboss/v1/features/{feature}/settings` to flip activation state, mapped to existing component options as in section 3.2.

### 6.5 Global Settings Search

- **UI location**
  - The search input is in `topHeader` as per Figma ("Search for settings…").
- **Behavior**
  - As the user types:
    - **Debounced calls** to `GET /buddyboss/v1/settings/search?query=...` with **300ms debounce delay**.
    - Shows a dropdown with results:
      - Feature name + icon.
      - Section title.
      - Setting label.
  - **Debounce Implementation**:
```javascript
function useDebounce(value, delay = 300) {
    const [debouncedValue, setDebouncedValue] = useState(value);
    
    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);
        
        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);
    
    return debouncedValue;
}

function SettingsSearch() {
    const [query, setQuery] = useState('');
    const debouncedQuery = useDebounce(query, 300); // 300ms debounce
    
    useEffect(() => {
        if (debouncedQuery.length >= 2) { // Minimum 2 characters
            wp.apiFetch({
                path: `/buddyboss/v1/settings/search?query=${encodeURIComponent(debouncedQuery)}`
            }).then(setResults);
        }
    }, [debouncedQuery]);
    
    return (
        <SearchControl
            value={query}
            onChange={setQuery}
            placeholder="Search for settings..."
        />
    );
}
```
  - On selecting a result:
    - The shell updates the route to `/settings/{featureId}/{sectionId}`.
    - The feature screen scrolls to the matching field and optionally highlights it.

### 6.6 Unsaved Changes Warning & Navigation Guards

**Problem**: Users may navigate away with unsaved changes, losing their work.

**Solution**: Implement `isDirty` state tracking and navigation guards:

- **State Management**:
```javascript
// Use React Context or wp.data for form state
const SettingsContext = React.createContext();

function SettingsProvider({ children }) {
    const [isDirty, setIsDirty] = useState(false);
    const [originalValues, setOriginalValues] = useState({});
    const [currentValues, setCurrentValues] = useState({});
    
    // Track changes
    const handleFieldChange = (fieldName, value) => {
        setCurrentValues(prev => ({ ...prev, [fieldName]: value }));
        setIsDirty(originalValues[fieldName] !== value);
    };
    
    // Save handler
    const handleSave = async () => {
        await wp.apiFetch({
            path: `/buddyboss/v1/features/${featureId}/settings`,
            method: 'POST',
            data: currentValues
        });
        setOriginalValues(currentValues);
        setIsDirty(false);
    };
    
    return (
        <SettingsContext.Provider value={{ isDirty, handleFieldChange, handleSave }}>
            {children}
        </SettingsContext.Provider>
    );
}
```

- **Navigation Guard**:
```javascript
function SettingsScreen() {
    const { isDirty } = useContext(SettingsContext);
    
    // Warn before navigation
    useEffect(() => {
        if (isDirty) {
            const handleBeforeUnload = (e) => {
                e.preventDefault();
                e.returnValue = '';
            };
            window.addEventListener('beforeunload', handleBeforeUnload);
            return () => window.removeEventListener('beforeunload', handleBeforeUnload);
        }
    }, [isDirty]);
    
    return (
        <div>
            {isDirty && (
                <Notice 
                    status="warning" 
                    isDismissible={false}
                    aria-live="polite"
                    aria-atomic="true"
                >
                    <strong>{__('You have unsaved changes.', 'buddyboss')}</strong>
                    {' '}
                    <Button onClick={handleSave} isPrimary>
                        {__('Save Changes', 'buddyboss')}
                    </Button>
                </Notice>
            )}
            {/* Form fields */}
        </div>
    );
}
```

- **Route Guard Component with Accessibility**:
```javascript
function ProtectedRoute({ children, isDirty, onSave }) {
    const [showConfirm, setShowConfirm] = useState(false);
    const [pendingNavigation, setPendingNavigation] = useState(null);
    const confirmRef = useRef(null);
    
    const handleNavigation = (path) => {
        if (isDirty) {
            setPendingNavigation(path);
            setShowConfirm(true);
        } else {
            navigate(path);
        }
    };
    
    const handleConfirm = () => {
        if (pendingNavigation) {
            navigate(pendingNavigation);
            setPendingNavigation(null);
        }
        setShowConfirm(false);
    };
    
    // Focus management for accessibility
    useEffect(() => {
        if (showConfirm && confirmRef.current) {
            confirmRef.current.focus();
        }
    }, [showConfirm]);
    
    // Announce to screen readers
    useEffect(() => {
        if (showConfirm) {
            wp.a11y.speak(__('Unsaved changes dialog opened', 'buddyboss'), 'assertive');
        }
    }, [showConfirm]);
    
    return (
        <>
            {showConfirm && (
                <Modal
                    onRequestClose={() => setShowConfirm(false)}
                    title={__('Unsaved Changes', 'buddyboss')}
                    aria-labelledby="unsaved-changes-title"
                    aria-describedby="unsaved-changes-description"
                    focusOnMount={true}
                >
                    <p id="unsaved-changes-description">
                        {__('You have unsaved changes. What would you like to do?', 'buddyboss')}
                    </p>
                    <div className="bb-modal-actions">
                        <Button
                            ref={confirmRef}
                            isPrimary
                            onClick={() => {
                                onSave().then(() => handleConfirm());
                            }}
                            aria-label={__('Save changes and navigate', 'buddyboss')}
                        >
                            {__('Save and Leave', 'buddyboss')}
                        </Button>
                        <Button
                            onClick={handleConfirm}
                            aria-label={__('Discard changes and navigate', 'buddyboss')}
                        >
                            {__('Leave Without Saving', 'buddyboss')}
                        </Button>
                        <Button
                            onClick={() => setShowConfirm(false)}
                            aria-label={__('Cancel navigation and stay on page', 'buddyboss')}
                        >
                            {__('Cancel', 'buddyboss')}
                        </Button>
                    </div>
                </Modal>
            )}
            {children}
        </>
    );
}
```

**Accessibility Improvements**:
- Modal has proper ARIA labels (`aria-labelledby`, `aria-describedby`)
- Focus management: Focus moves to first action button when modal opens
- Keyboard navigation: All buttons are keyboard accessible
- Screen reader announcements: Changes announced via `wp.a11y.speak()`
- Focus trap: Focus cannot escape modal while open (handled by WordPress Modal component)
- Escape key: Closes modal (standard WordPress Modal behavior)
- Notice component: Uses `aria-live="polite"` for screen reader announcements

### 6.7 React Error Boundaries

**Problem**: Errors in feature bundles can crash the entire admin app.

**Solution**: Implement Error Boundaries to isolate errors:

- **Error Boundary Component**:
```javascript
class FeatureErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }
    
    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }
    
    componentDidCatch(error, errorInfo) {
        console.error('Feature bundle error:', error, errorInfo);
        
        if (window.bbErrorTracking) {
            window.bbErrorTracking.captureException(error, {
                contexts: { react: errorInfo },
                tags: { feature: this.props.featureId }
            });
        }
    }
    
    render() {
        if (this.state.hasError) {
            return (
                <div className="bb-error-boundary">
                    <h2>Something went wrong</h2>
                    <p>The {this.props.featureName || 'feature'} encountered an error.</p>
                    <button onClick={() => window.location.reload()}>Reload Page</button>
                </div>
            );
        }
        return this.props.children;
    }
}
```

### 6.8 Bundle Loading Failures

**Problem**: Feature bundles may fail to load (network error, 404, syntax error).

**Solution**: Implement fallback behavior and error UI:

- **Bundle Load Detection with Cancellation**:
```javascript
function useFeatureBundle(featureId) {
    const [status, setStatus] = useState('loading');
    const [error, setError] = useState(null);
    const [Component, setComponent] = useState(null);
    
    useEffect(() => {
        let cancelled = false;
        let importPromise = null;
        
        // Start loading
        importPromise = import(`./features/${featureId}/SettingsScreen`)
            .then(module => {
                if (!cancelled) {
                    setComponent(() => module.default);
                    setStatus('loaded');
                }
            })
            .catch(err => {
                if (!cancelled) {
                    setError(err);
                    setStatus('error');
                }
            });
        
        // Cleanup: Cancel loading if component unmounts or featureId changes
        return () => {
            cancelled = true;
            // Note: Dynamic import() cannot be cancelled, but we prevent state updates
            // For true cancellation, consider using AbortController with fetch-based loading
        };
    }, [featureId]);
    
    return { status, error, Component };
}

// Alternative: Fetch-based loading with AbortController for true cancellation
function useFeatureBundleWithAbort(featureId) {
    const [status, setStatus] = useState('loading');
    const [error, setError] = useState(null);
    const [Component, setComponent] = useState(null);
    
    useEffect(() => {
        const abortController = new AbortController();
        
        // Load bundle via fetch (if using custom loader)
        fetch(`/wp-content/plugins/buddyboss-platform/assets/js/features/${featureId}.js`, {
            signal: abortController.signal
        })
            .then(response => {
                if (!response.ok) throw new Error('Bundle not found');
                return response.text();
            })
            .then(code => {
                // Evaluate code (in production, use proper module system)
                // This is simplified - actual implementation would use proper module loading
                const module = eval(code);
                setComponent(() => module.default);
                setStatus('loaded');
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    setError(err);
                    setStatus('error');
                }
            });
        
        // Cleanup: Abort fetch on unmount
        return () => {
            abortController.abort();
        };
    }, [featureId]);
    
    return { status, error, Component };
}
```

- **Error UI Component**:
```javascript
function BundleLoadError({ error, featureId }) {
    return (
        <div className="bb-bundle-load-error">
            <Icon icon="warning" />
            <h2>Failed to Load Feature</h2>
            <p>The {featureId} feature could not be loaded.</p>
            <Button onClick={() => window.location.reload()}>Retry</Button>
            <Button onClick={() => navigate('/settings')}>Back to Settings</Button>
        </div>
    );
}
```

### 6.9 Mobile Responsiveness Strategy

**Problem**: Admin UI must work on mobile devices.

**Solution**: Implement responsive design with breakpoints:

- **Breakpoint Strategy**:
```css
/* Mobile-first approach */
.bb-admin-container { padding: 1rem; }
@media (min-width: 768px) { .bb-admin-container { padding: 2rem; } }
@media (min-width: 1024px) { .bb-admin-container { padding: 2rem 3rem; } }
```

- **Mobile Navigation Patterns**:
```javascript
function MobileNav({ items }) {
    const [isOpen, setIsOpen] = useState(false);
    return (
        <>
            <button className="bb-mobile-nav-toggle" onClick={() => setIsOpen(!isOpen)}>
                <Icon icon="menu" />
            </button>
            {isOpen && (
                <div className="bb-mobile-nav-overlay" onClick={() => setIsOpen(false)}>
                    <nav className="bb-mobile-nav">
                        {items.map(item => <NavItem key={item.id} item={item} />)}
                    </nav>
                </div>
            )}
        </>
    );
}
```

- **Touch-Friendly Controls**:
  - Minimum touch target size: 44x44px
  - Adequate spacing between interactive elements
  - Responsive tables: Horizontal scroll on mobile, or card-based layout
  - Collapsible sections on mobile to save space

### 6.10 Technologies & Conventions (WordPress React)

- Use **WordPress-provided globals** rather than bundling React separately at runtime:
  - `const { createElement, useState, useEffect } = wp.element;`
  - `const { Button, Card, TextControl, ToggleControl, SelectControl, TabPanel } = wp.components;`
  - `const { __ } = wp.i18n;`
  - `const apiFetch = wp.apiFetch;`
- This aligns with existing ReadyLaunch and Onboarding code and keeps JS payload smaller.
- Styling:
  - Use existing BuddyBoss admin styles and design tokens where possible.
  - For new components, follow existing CSS/SCSS patterns rather than introducing Tailwind or other utility frameworks.

## 7. Settings Feature List Page (Figma “Settings”)

This page replaces the old tabbed settings index and shows **features as cards**.

### 7.1 Data & Layout (Figma “settings” Frame)

- **Global layout**
  - Reuses the BuddyBoss admin shell:
    - Black WP admin top bar (`top-bar`).
    - Left WordPress admin menu (`adminMenu`).
    - BuddyBoss top header (`topHeader`) with **global settings search** input and icons (notifications, documentation).
  - Main content under `/BuddyBoss → Settings`:
    - A **filter bar** frame named `filter` at the top of the content area.
    - Below it, a large grid (`Frame 5931`) of **feature cards** grouped into three sections.

- **Filter bar**
  - Left side: `tab` instance – used for the **All / Active / Inactive** style filters (tab-like pill buttons).
  - Right side:
    - A primary `inputSelect` for **Category** or **Sort** (e.g. filter by category like “BuddyBoss Community Settings”, “Add-ons”, “Integrations”).
    - Optional layout toggle (list vs grid) currently hidden in Figma (`layout`).

- **Feature groups and cards**
  - Section 1: **BUDDYBOSS COMMUNITY SETTINGS**
    - Text node “BUDDYBOSS COMMUNITY SETTINGS”.
    - Grid of `cardAddon` instances, each representing a **core feature** (Activity, Groups, Profiles, Forums, Messages, Media, etc.).
  - Section 2: **BUDDYBOSS ADD-ONS**
    - Text node “BUDDYBOSS ADD-ONS”.
    - Grid of `cardAddon` instances for **BuddyBoss modules/add-ons**.
  - Section 3: **BUDDYBOSS INTEGRATIONS**
    - Text node “BUDDYBOSS INTEGRATIONS”.
    - Grid of `cardAddon` instances for **integrations** (e.g. LMS, OneSignal, Zoom, etc.).

- **Each card** (from `cardAddon` component) visually includes:
  - Feature icon/logo.
  - Title and description.
  - A status element (Active/Inactive or Pro/Plus badge).
  - A call-to-action (e.g. “Configure” / “Activate” / “Upgrade”).

### 7.1.1 Mapping to Feature Registry & REST

- `GET /features` must return data structured so it can be rendered exactly as in Figma:
  - `group` (one of `community_settings`, `addons`, `integrations`).
  - `label`:
    - “BuddyBoss Community Settings”
    - “BuddyBoss Add-ons”
    - “BuddyBoss Integrations”
  - `cards[]`:
    - `id` (feature id, e.g. `activity`, `groups`, `forums`).
    - `title`, `description`, `icon`.
    - `status` (`active` / `inactive` based on registry `is_active_callback`).
    - `license_tier` (`free`, `pro`, `plus`) and flags for showing Pro/Plus badges.
    - `route` to navigate to (e.g. `/settings/activity`).

- **Filters and counts**:
  - Tabs:
    - All – all features.
    - Active – features where `status === 'active'`.
    - Inactive – features where `status === 'inactive'`.
  - Category dropdown (primary `inputSelect`):
    - Filters by **groups** or by more granular categories if you define them in the registry.
  - REST should expose aggregate counts for All, Active, Inactive, and per group to display in the UI.

### 7.2 Navigation

- Clicking a feature:
  - Navigates to `/settings/{featureId}`.
  - Loads its sections + fields via `GET /features/{featureId}`.

## 8. Feature Detail Settings Pages

These pages correspond to the detailed Figma designs (e.g., Activity Settings and Group Settings) with left side navigation.

### 8.1 Left Side Navigation (Figma “activitySettings” & “groupSettings”)

- The **left nav** for per-feature settings is a dedicated `navMenu` component anchored between the admin menu and the main settings content:
  - For Activity (from `activitySettings` frame) there will be items like:
    - Activity Settings
    - Activity Comments
    - Activity Topics
    - Posts Visibility
    - Activity Sharing
    - Access Controls
    - All Activity (list screen)
  - For Groups (`groupSettings` frame `navMenu`):
    - **Back to Settings** button (navigates to the main feature grid).
    - Section items:
      - Group Settings
      - Group Images
      - Group Headers
      - Group Directory
      - Access Controls
      - All Groups
      - Group Types
      - Group Navigation

- Mapping to registry:
  - Each nav item is a **section or screen** registered under the feature:
    - Example for Groups:
      - `groups.settings` (Group Settings)
      - `groups.images` (Group Images)
      - `groups.headers` (Group Headers)
      - `groups.directories` (Group Directory)
      - `groups.access_control` (Access Controls)
      - `groups.all_groups` (All Groups screen)
      - `groups.types` (Group Types screen)
      - `groups.navigation` (Group Navigation screen)
  - Feature Registry should store:
    - `section_id`, `title`, `icon`, `order`, `type` (`settings` vs `screen`), and `route`.

- React behavior:
  - Clicking nav items:
    - Updates route (e.g. `/settings/groups/settings`, `/settings/groups/images`, `/settings/groups/all`).
    - Scrolls to the section (for settings) or switches to a list screen (for All Groups / types / navigation).
  - Third-party sections:
    - When registered with `bb_register_feature_section( 'groups', 'my_plugin_section', ... )`, they appear automatically in this nav with the provided `title` and `icon`.
  - Third-party navigation items:
    - When registered with `bb_register_feature_nav_item( 'groups', ... )`, they appear automatically in the sidebar.
    - React `SideNavigation` component reads from REST API and renders all registered nav items.
    - No frontend code required - React automatically renders all registered items.

### 8.2 Field Rendering & Saving

- Render fields using a **generic component library**:
  - Map `type` → UI control:
    - `checkbox` / `toggle` → `ToggleControl`.
    - `text` → `TextControl`.
    - `select` → `SelectControl`.
    - `textarea` → `TextareaControl`.
  - Support additional metadata: help text, tooltips, pro/plus badges.

- Saving:
  - On Save:
    - Gather changed fields and `POST /features/{featureId}/settings`.
  - On success:
    - Show notification / snackbar.
    - Update local state.

## 9. Activity Feature Plan

### 9.1 Activity Settings Migration (Figma-aligned)

- Figma sections (as individual featureCards/sectionTitle + settingsSection instances) to map:
  - Activity Settings (top card).
  - Activity Comments.
  - Activity Topics.
  - Posts Visibility.
  - Activity Sharing.
  - Access Controls.

- Implementation steps:
  - Identify all Activity-related settings in:
    - `bp-admin-setting-activity.php`.
    - Any related general settings or performance options tied to Activity.
  - For each setting:
    - Register via `bb_register_feature_field( 'activity', $section_id, $field_args );`.
    - Associate to appropriate sections (`activity_settings`, `activity_comments`, etc.).
    - Add metadata to match design (labels, descriptions, license tier where needed).

### 9.2 All Activity List (React)

- Replace `BP_Activity_List_Table` screen with a React list.

- **Back-end:**
  - Build REST endpoints:
    - `GET /activity` – paginated list of activities (with filters).
    - `POST /activity/{id}/update` – update content/status.
    - `DELETE /activity/{id}` – delete.
    - Possibly actions for bulk operations.
  - Use existing `BP_Activity_Activity` and supporting APIs to implement.

- **Front-end (React):**
  - Table with:
    - Search, filter by type/status/date, pagination.
    - Bulk checkboxes and actions.
    - Single row actions (Edit, Trash, etc.).
  - UI built with `wp.components` and `wp.apiFetch`.

### 9.3 Extensibility Hooks for Activity

The new Activity architecture must remain extensible for third-party plugins:

- **PHP-level hooks**
  - Preserve existing hooks:
    - `bp_activity_admin_load`, `bp_hide_meta_boxes`, filters around Activity queries, etc.
  - For new REST endpoints (once implemented):
    - Add filters/actions:
      - `buddyboss_rest_activity_query_args` – to adjust query args for `GET /buddyboss/v1/activity`.
      - `buddyboss_rest_activity_prepare_item` – to modify REST response per activity.
      - `buddyboss_rest_activity_update_item` – to hook additional logic on updates.
- **Feature Registry hooks**
  - Allow third parties to add additional Activity settings:
    - Via `bb_register_feature_section( 'activity', 'my_plugin_section', $args )`.
    - Via `bb_register_feature_field( 'activity', 'my_plugin_section', $field_args )`.
  - These fields will:
    - Appear in Activity’s left nav (as new sections).
    - Be included in settings search results.
    - Be returned in `GET /buddyboss/v1/features/activity`.
- **React-level integration**
  - The Activity settings screen should:
    - Render **sections and fields from data**, not hard-coded JSX.
    - Automatically display any registered third-party Activity sections/fields without frontend code changes.

### 9.4 Summary for Activity Migration

- **Settings**:
  - Move all Activity-related options from `bp-admin-setting-activity.php` into the Feature Registry under `feature_id = 'activity'`, grouped into sections that match Figma (Activity Settings, Comments, Topics, Visibility, Sharing, Access Controls), keeping option names unchanged.
  - Implement a React Activity settings screen that reads from `/buddyboss/v1/features/activity` and writes via `/buddyboss/v1/features/activity/settings`, with a left nav including All Activity as a link to the list route.
- **All Activity list**:
  - Implement a React list screen at `/activity/all` using a new `/buddyboss/v1/activity` REST controller, with filters, search, pagination, bulk actions, and row actions that replicate current behaviour.
  - Remove the old `BP_Activity_List_Table` UI once the React version is stable, keeping all low-level Activity APIs and business logic intact.
- **Extensibility**:
  - Provide REST and registry hooks for third parties to customize queries, responses, updates, and to register new Activity-related settings sections/fields that automatically surface in the React UI and search.

## 10. Groups Feature Plan

### 10.1 Groups Settings Migration (Figma-aligned)

- Figma sections for Groups (from `groupSettings` frame):
  - **Group Settings** (featureCard with switches for group creation, subscriptions, group messages, etc.).
  - **Group Images**.
  - **Group Headers**.
  - **Group Directory**.
  - **Access Controls**.
  - **Subgroups** / Hierarchies (Subgroups featureCard in design).
  - **Group Types** (separate screens for list and add/edit).
  - **Group Navigation** (dedicated navigation designer screen).

- Implementation steps:
  - Use `bp-admin-setting-groups.php` as the source:
    - Map each `add_section()` to `bb_register_feature_section( 'groups', ... );`.
    - Map each `add_field()` to `bb_register_feature_field( 'groups', sectionId, ... );`.
  - Ensure:
    - Existing options names remain unchanged.
    - Pro/Plus flags map to `license_tier` / `pro_only` in registry.

### 10.2 Detailed Groups Settings Migration

- **Feature Registry setup**
  - Register Groups as `feature_id = 'groups'` in the Feature Registry.
  - Map all existing options from `bp-admin-setting-groups.php` into registry sections matching Figma:
    - `groups_settings` – Group Settings card (group creation, subscriptions, group messages).
    - `groups_images` – Group Images (avatars, cover images).
    - `groups_headers` – Group Headers (header styles, layouts).
    - `groups_directory` – Group Directory (layout, elements, filters).
    - `groups_access_control` – Access Controls.
    - `groups_hierarchies` – Subgroups/Hierarchies (enable hierarchies, hide subgroups, restrict invitations).
  - For each option:
    - Keep the **exact same option name** (e.g. `bp_restrict_group_creation`, `bp-disable-group-avatar-uploads`, etc.).
    - Register via `bb_register_feature_field( 'groups', <section_id>, $field_args )` with:
      - Correct field type (toggle, select, text, etc.).
      - Existing sanitize callback.
      - Updated labels/descriptions matching Figma copy.
      - License tier flags (`pro_only`, `plus_only`) where applicable.

- **React Groups Settings Screen**
  - Build a React screen at route `/settings/groups` that:
    - Reads all sections/fields from `GET /buddyboss/v1/features/groups`.
    - Writes back via `POST /buddyboss/v1/features/groups/settings`.
    - Uses a left `navMenu` (as per Figma) with entries:
      - Group Settings
      - Group Images
      - Group Headers
      - Group Directory
      - Access Controls
      - All Groups (link to `/groups/all`)
      - Group Types (link to `/groups/types`)
      - Group Navigation (link to `/groups/navigation`)
  - Each section renders as a `featureCard` with `sectionTitle` and `settingsSection` rows matching Figma design.
  - Once fully migrated and verified, remove the PHP-rendered Groups settings UI under `bp-settings`, keeping only the registry + REST.

### 10.3 All Groups List (React + REST)

- **REST Controller**
  - Create `BB_REST_Groups_Controller` at `/buddyboss/v1/groups`:
    - `GET /groups`:
      - Paginated list with filters (`page`, `per_page`, `search`, `status`, `type`, `parent_id` for hierarchies, `user_id` for member groups).
      - Returns group objects with: `id`, `name`, `slug`, `description`, `status`, `type`, `parent_id`, `member_count`, `avatar`, `cover_image`, `date_created`, `last_activity`.
      - Uses existing `groups_get_groups()` and `BP_Groups_Group` APIs internally.
    - `GET /groups/{id}`:
      - Single group details including members list, settings, type, hierarchy info.
    - `POST /groups/{id}`:
      - Update group details (name, description, status, privacy, type, parent).
    - `DELETE /groups/{id}`:
      - Delete group (with optional forum deletion if applicable).
    - `POST /groups/batch`:
      - Bulk operations (delete, change status, change type).

- **React All Groups Screen**
  - Build a React screen at route `/groups/all`:
    - Table/list view with:
      - Search input.
      - Filters: Status (public/private/hidden), Group Type, Hierarchy level, Date range.
      - Pagination controls.
      - Bulk checkboxes and actions dropdown (Delete, Change Status, Change Type).
      - Row actions: View (frontend link), Edit (navigate to `/groups/{id}/edit`), Delete, Manage Members.
    - Uses `wp.components` (`Table`, `Button`, `SelectControl`, `TextControl`, `Spinner`) and `wp.apiFetch` to call REST endpoints.
    - Matches functionality of current `BP_Groups_List_Table` but with modern React UI.
  - After launch, deprecate and remove `BP_Groups_List_Table` HTML rendering from `bp-groups-admin.php`, but keep all low-level Groups APIs (`groups_create_group()`, `groups_delete_group()`, etc.).

### 10.4 Create Group (React Form + REST)

- **REST Endpoint**
  - `POST /buddyboss/v1/groups`:
    - Accepts payload: `name`, `description`, `status` (public/private/hidden), `group_type`, `parent_id` (for hierarchies), `invite_members` (array of user IDs).
    - Validates required fields and permissions.
    - Calls `groups_create_group()` internally.
    - Returns created group object with `id`.

- **React Create Group Screen**
  - Build a React screen at route `/groups/create`:
    - Form fields matching existing admin create flow:
      - Group Name (required).
      - Description (textarea).
      - Privacy/Status (radio/select: Public, Private, Hidden).
      - Group Type (select dropdown, if group types enabled).
      - Parent Group (select dropdown, if hierarchies enabled).
      - Invite Members (autocomplete/select multiple users).
    - Submit button calls `POST /groups` and on success:
      - Shows success notice.
      - Redirects to `/groups/{id}/edit` or `/groups/all`.
    - Uses `wp.components` form controls and validation.

### 10.5 Edit Group (React Form + REST)

- **REST Endpoints**
  - `GET /buddyboss/v1/groups/{id}`:
    - Returns full group details including:
      - Basic info (name, description, status, type, parent).
      - Members list (with roles: admin, mod, member).
      - Settings (invitations, subscriptions, etc.).
  - `POST /buddyboss/v1/groups/{id}`:
    - Updates group details.
  - `POST /buddyboss/v1/groups/{id}/members`:
    - Add/remove members, change member roles.
  - `GET /buddyboss/v1/groups/{id}/members`:
    - List members with pagination.

- **React Edit Group Screen**
  - Build a React screen at route `/groups/{id}/edit`:
    - Tabs or sections:
      - **Group Details**: Name, description, status, type, parent group.
      - **Settings**: Privacy, invitations, subscriptions, group messages.
      - **Members**: List of members with roles, add/remove members, change roles.
      - **Group Type**: Assign/change group type.
      - **Hierarchy**: Set parent group, view subgroups.
    - Loads initial data from `GET /groups/{id}`.
    - Saves via `POST /groups/{id}` and member changes via `/groups/{id}/members`.
    - Matches functionality of current Groups edit metaboxes but in a unified React form.
  - After launch, remove old Groups edit metabox UI from `bp-groups-admin.php`, keeping only the REST layer and low-level APIs.

### 10.6 Per-Group Settings & Meta Fields (React + REST)

**Critical**: Handle group-specific settings that are stored as group meta (per-group, not global).

- **Current state**:
  - Some group settings are stored as **group meta** (via `groups_update_groupmeta()` / `groups_get_groupmeta()`).
  - Third-party plugins can add custom metaboxes via `bp_groups_admin_meta_boxes` hook.
  - These metaboxes contain per-group settings (e.g., custom fields, plugin-specific options).
- **Migration strategy**:
  - **Registry API for per-group settings**:
    - Extend Feature Registry with `bb_register_group_setting()` function:
```php
bb_register_group_setting( string $setting_id, array $args );
```
    - **Parameters**:
      - `$setting_id`: Unique identifier (e.g., `'my_plugin_group_enabled'`).
      - `$args`: Array with:
        - `label`: Field label.
        - `type`: Field type (`toggle`, `text`, `select`, etc.).
        - `description`: Help text.
        - `default`: Default value.
        - `sanitize_callback`: Sanitization function.
        - `section`: Which section in Group Edit screen (`'group_settings'`, `'group_details'`, etc.).
        - `order`: Sort order within section.
        - `meta_key`: Optional custom meta key (defaults to `$setting_id`).
  - **Example registration**:
```php
add_action( 'bb_register_features', 'my_plugin_register_group_settings' );
function my_plugin_register_group_settings() {
    bb_register_group_setting( 'my_plugin_group_enabled', array(
        'label'            => __( 'Enable My Plugin', 'my-plugin' ),
        'type'             => 'toggle',
        'description'      => __( 'Enable My Plugin features for this group.', 'my-plugin' ),
        'default'          => 0,
        'sanitize_callback' => 'intval',
        'section'          => 'group_settings',
        'order'            => 100,
        'meta_key'         => 'my_plugin_enabled', // Optional: custom meta key
    ) );
}
```
  - **REST endpoints**:
    - `GET /buddyboss/v1/groups/{id}/settings`:
      - Returns all registered per-group settings with current values.
      - Reads from group meta via `groups_get_groupmeta()`.
    - `POST /buddyboss/v1/groups/{id}/settings`:
      - Accepts payload: `{ setting_id: value, ... }`.
      - Updates group meta via `groups_update_groupmeta()`.
      - Validates and sanitizes using registered callbacks.
- **React Group Edit screen integration**:
  - Group Edit screen at `/groups/{id}/edit`:
    - Loads per-group settings from `GET /groups/{id}/settings`.
    - Renders settings in appropriate sections (e.g., "Group Settings" tab).
    - Saves via `POST /groups/{id}/settings`.
    - Settings appear alongside global Groups settings but are clearly labeled as "per-group".
- **Backward compatibility**:
  - **Existing metaboxes**:
    - Plugins using `bp_groups_admin_meta_boxes` hook can continue to work during transition.
    - Old metaboxes render in old PHP UI (if still accessible).
    - New registry settings render in React UI.
    - Plugins can migrate gradually.
  - **Existing meta access**:
    - `groups_get_groupmeta()` / `groups_update_groupmeta()` continue to work.
    - New registry API is additive, not replacing.
    - Plugins can use either old meta API or new registry API.
- **Migration of existing group meta fields**:
  - Audit all existing `groups_update_groupmeta()` calls in BuddyBoss core.
  - Identify which are "settings" (user-configurable) vs. "internal data" (system-managed).
  - Register user-configurable settings via `bb_register_group_setting()`.
  - Internal data remains as meta but doesn't appear in UI.
- **Third-party plugin migration path**:
  - **Option 1**: Continue using metaboxes (old UI only).
  - **Option 2**: Register via `bb_register_group_setting()` (new React UI only).
  - **Option 3**: Do both during transition (works in both UIs).
  - Provide migration guide and examples.

### 10.7 Group Types Management (React + REST)

- **REST Controller**
  - Create `BB_REST_Group_Types_Controller` at `/buddyboss/v1/group-types`:
    - `GET /group-types`:
      - List all registered group types with metadata (name, labels, description, icon, etc.).
    - `POST /group-types`:
      - Register a new group type (calls `bp_groups_register_group_type()` internally).
    - `GET /group-types/{id}`:
      - Single group type details.
    - `POST /group-types/{id}`:
      - Update group type metadata.
    - `DELETE /group-types/{id}`:
      - Unregister group type.

- **React Group Types Screens**
  - **List Screen** (`/groups/types`):
    - Table showing all group types with:
      - Name, description, icon.
      - Count of groups using this type.
      - Actions: Edit, Delete.
    - "Add New Group Type" button navigates to `/groups/types/new`.
  - **Add/Edit Screen** (`/groups/types/new` or `/groups/types/{id}/edit`):
    - Form fields:
      - Type identifier (slug, read-only on edit).
      - Name (singular and plural labels).
      - Description.
      - Icon (select or upload).
      - Enable/disable toggle.
    - Submit calls `POST /group-types` or `POST /group-types/{id}`.
    - Matches Figma "Add/Edit group type" design.

### 10.8 Group Navigation Designer (React + REST)

- **REST Endpoint**
  - `GET /buddyboss/v1/groups/navigation`:
    - Returns current group navigation configuration (tabs, order, visibility, labels).
  - `POST /buddyboss/v1/groups/navigation`:
    - Updates navigation configuration (stores in options or custom table).

- **React Group Navigation Screen**
  - Build a React screen at route `/groups/navigation`:
    - Drag-and-drop interface for reordering tabs.
    - Toggle visibility for each tab.
    - Edit labels for each tab.
    - Preview of navigation structure.
    - Matches Figma "Group Navigation" design.
  - Uses `wp.components` and potentially a drag-and-drop library compatible with WordPress React (or custom implementation).

### 10.9 Extensibility Hooks for Groups

The new Groups architecture must remain extensible for third-party plugins:

- **PHP-level hooks**
  - Preserve existing hooks:
    - `bp_groups_admin_load`, `bp_groups_admin_meta_boxes`, filters around Groups queries, group type registration, etc.
  - For new REST endpoints:
    - Add filters/actions:
      - `buddyboss_rest_groups_query_args` – to adjust query args for `GET /buddyboss/v1/groups`.
      - `buddyboss_rest_groups_prepare_item` – to modify REST response per group.
      - `buddyboss_rest_groups_update_item` – to hook additional logic on updates.
      - `buddyboss_rest_group_types_query_args` – for group types queries.
- **Feature Registry hooks**
  - Allow third parties to add additional Groups settings:
    - Via `bb_register_feature_section( 'groups', 'my_plugin_section', $args )`.
    - Via `bb_register_feature_field( 'groups', 'my_plugin_section', $field_args )`.
  - These fields will:
    - Appear in Groups’ left nav (as new sections).
    - Be included in settings search results.
    - Be returned in `GET /buddyboss/v1/features/groups`.
- **React-level integration**
  - The Groups settings screen should:
    - Render **sections and fields from data**, not hard-coded JSX.
    - Automatically display any registered third-party Groups sections/fields without frontend code changes.
  - Group edit screen can expose hooks for third-party tabs/sections via registry or filter system.

### 10.10 Summary for Groups Migration

- **Settings**:
  - Move all Groups-related options from `bp-admin-setting-groups.php` into the Feature Registry under `feature_id = 'groups'`, grouped into sections matching Figma (Group Settings, Images, Headers, Directory, Access Controls, Hierarchies), keeping option names unchanged.
  - Implement a React Groups settings screen that reads from `/buddyboss/v1/features/groups` and writes via `/buddyboss/v1/features/groups/settings`, with a left nav including links to All Groups, Group Types, and Group Navigation.
- **All Groups list**:
  - Implement a React list screen at `/groups/all` using a new `/buddyboss/v1/groups` REST controller, with filters, search, pagination, bulk actions, and row actions that replicate current behaviour.
  - Remove the old `BP_Groups_List_Table` UI once the React version is stable, keeping all low-level Groups APIs intact.
- **Create/Edit Group**:
  - Implement React forms at `/groups/create` and `/groups/{id}/edit` using REST endpoints, replacing old metabox-based edit UI.
- **Group Types**:
  - Implement React screens for listing and managing group types at `/groups/types`, using a new `/buddyboss/v1/group-types` REST controller.
- **Group Navigation**:
  - Implement a React designer screen at `/groups/navigation` for customizing group navigation tabs.
- **Extensibility**:
  - Provide REST and registry hooks for third parties to customize queries, responses, updates, and to register new Groups-related settings sections/fields that automatically surface in the React UI and search.

## 11. Settings Search Functionality

### 11.1 Indexing

- Build an index of all settings in the registry:
  - Each field yields entries with:
    - `featureId`
    - `sectionId`
    - `fieldName`
    - `featureLabel`, `sectionTitle`, `fieldLabel`
    - Derived `breadcrumb` string.

- Offer via REST:
  - `GET /settings/search?query=...`

### 11.2 UI Behavior

- Search box in the admin header (as per Figma):
  - As user types:
    - Debounced calls to `/settings/search`.
    - Show list of matching settings with:
      - Feature icon + name.
      - Section name + field name.
  - On selecting a match:
    - Navigate to `/settings/{featureId}/{sectionId}`.
    - Optionally highlight the field.

## 12. Extensibility API for Third-Party Plugins

You want:

> A way for registering custom side navigation, sections, and fields for third-party plugins to register and render.

The Feature Registry provides a comprehensive API for third-party plugins to integrate seamlessly into the new React admin UI.

### 12.1 Registering a New Feature

Third-party plugins can register their own features that appear as cards on the Settings page:

- **Function**: `bb_register_feature( string $feature_id, array $args )`
- **When to call**: Hook into `bb_register_features` action (fired after BuddyBoss core features are registered).
- **Example**:
```php
add_action( 'bb_register_features', 'my_plugin_register_feature' );
function my_plugin_register_feature() {
    bb_register_feature( 'my_plugin', array(
        'label'              => __( 'My Plugin', 'my-plugin' ),
        'description'        => __( 'Description of what this feature does.', 'my-plugin' ),
        'icon'               => 'dashicons-admin-generic', // Dashicon slug or custom SVG identifier
        'category'           => 'add-ons', // 'community', 'add-ons', 'integrations'
        'license_tier'       => 'free', // 'free', 'pro', 'plus'
        'is_available_callback' => 'my_plugin_is_available', // Returns bool
        'is_active_callback'     => 'my_plugin_is_active', // Returns bool (checks option)
        'settings_route'     => '/settings/my-plugin',
        'php_loader'         => 'my_plugin_load_php', // Optional: callback to load PHP code only if active
    ) );
}
```

- **Result**:
  - Feature card appears in Settings grid under appropriate category.
  - Card shows status badge (Active/Inactive) based on `is_active_callback`.
  - Clicking card navigates to `/settings/my-plugin`.
  - If `is_active()` returns false, PHP code from `php_loader` is not loaded (code compartmentalization).

### 12.2 Extending Existing Features (Adding Sections & Fields)

Third-party plugins can add settings sections and fields to existing BuddyBoss features (Activity, Groups, etc.):

- **Register a new section**:
  - **Function**: `bb_register_feature_section( string $feature_id, string $section_id, array $args )`
  - **Example**:
```php
add_action( 'bb_register_features', 'my_plugin_extend_activity' );
function my_plugin_extend_activity() {
    bb_register_feature_section( 'activity', 'my_plugin_activity_settings', array(
        'title'       => __( 'My Plugin Activity Settings', 'my-plugin' ),
        'description' => __( 'Customize how My Plugin interacts with Activity.', 'my-plugin' ),
        'nav_group'   => 'Extensions', // Groups this section in left nav
        'order'       => 100, // Sort order within nav_group
        'is_default'  => false, // Whether to open this section by default
    ) );
}
```

- **Register fields within a section**:
  - **Function**: `bb_register_feature_field( string $feature_id, string $section_id, array $field_args )`
  - **Example**:
```php
// Register a toggle field
bb_register_feature_field( 'activity', 'my_plugin_activity_settings', array(
    'name'             => 'my_plugin_activity_enabled', // Option name (stored in DB)
    'label'            => __( 'Enable My Plugin Activity Integration', 'my-plugin' ),
    'type'             => 'toggle', // 'toggle', 'checkbox', 'text', 'textarea', 'select', 'radio'
    'description'      => __( 'When enabled, My Plugin will sync with Activity feed.', 'my-plugin' ),
    'default'          => 0,
    'sanitize_callback' => 'intval', // WordPress sanitize callback
    'pro_only'         => false, // Show Pro badge if true
    'license_tier'     => 'free', // 'free', 'pro', 'plus'
) );

// Register a select field
bb_register_feature_field( 'activity', 'my_plugin_activity_settings', array(
    'name'             => 'my_plugin_activity_mode',
    'label'            => __( 'Activity Mode', 'my-plugin' ),
    'type'             => 'select',
    'description'      => __( 'Choose how My Plugin handles activity.', 'my-plugin' ),
    'default'          => 'sync',
    'options'          => array(
        'sync'    => __( 'Sync', 'my-plugin' ),
        'replace' => __( 'Replace', 'my-plugin' ),
        'append'  => __( 'Append', 'my-plugin' ),
    ),
    'sanitize_callback' => 'sanitize_text_field',
) );
```

- **Result**:
  - New section appears in left navigation for that feature (e.g., under "Extensions" group in Activity settings).
  - Fields appear in the React UI automatically, rendered based on `type`.
  - Settings are saved to the same option names via existing `register_setting()` mechanism.
  - Fields are searchable via global Settings Search.

### 12.3 Side Panel Navigation Items (Left Sidebar)

**Critical**: Third-party plugins can register side panel navigation items that are automatically rendered by React in the left sidebar (`navMenu`).

The left navigation panel (as shown in the [Figma design](https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2086-40188&m=dev)) is built automatically from registered sections and navigation items.

#### 12.3.1 Automatic Navigation from Sections

- **Sections automatically become nav items**:
  - When you register a section via `bb_register_feature_section()`, it automatically appears in the left sidebar.
  - Sections are grouped by `nav_group` (e.g., "Activity", "Access Control", "Extensions").
  - Within each group, sections are sorted by `order`.
  - The `is_default` flag determines which section opens first when navigating to `/settings/{featureId}`.

- **Example**: Registering a section automatically creates a nav item:
```php
bb_register_feature_section( 'groups', 'my_plugin_group_settings', array(
    'title'       => __( 'My Plugin Group Settings', 'my-plugin' ),
    'description' => __( 'Configure My Plugin for Groups.', 'my-plugin' ),
    'nav_group'   => 'Extensions', // Appears in "Extensions" group in sidebar
    'order'       => 10,
) );
// This section automatically appears in the left sidebar as a clickable nav item
```

#### 12.3.2 Registering Custom Navigation Items

For screens that are NOT settings sections (like list screens, custom admin pages, etc.), plugins can register standalone navigation items:

- **Function**: `bb_register_feature_nav_item( string $feature_id, array $args )`
- **When to use**: For non-settings screens like "All Activity", "All Groups", custom list screens, reports, etc.
- **Example**:
```php
add_action( 'bb_register_features', 'my_plugin_register_nav_items' );
function my_plugin_register_nav_items() {
    // Register a nav item for a custom Activity log screen
    bb_register_feature_nav_item( 'activity', array(
        'id'         => 'my_plugin_activity_log', // Unique ID
        'label'      => __( 'My Plugin Activity Log', 'my-plugin' ),
        'route'      => '/activity/my-plugin-log', // React route (must be handled by your React component)
        'icon'       => 'dashicons-list-view', // Dashicon slug or custom icon identifier
        'nav_group'  => 'Activity', // Which group in sidebar (or create new group)
        'order'      => 200, // Sort order within nav_group
        'badge'      => null, // Optional: badge count or text
        'is_external' => false, // If true, opens in new tab (for external URLs)
    ) );
    
    // Register a nav item for a custom Groups report screen
    bb_register_feature_nav_item( 'groups', array(
        'id'         => 'my_plugin_groups_report',
        'label'      => __( 'Groups Report', 'my-plugin' ),
        'route'      => '/groups/my-plugin-report',
        'icon'       => 'dashicons-chart-bar',
        'nav_group'  => 'Extensions', // Appears in "Extensions" group
        'order'      => 50,
    ) );
}
```

#### 12.3.3 Navigation Item Parameters

**Required parameters**:
- `id`: Unique identifier for the nav item
- `label`: Display text in the sidebar
- `route`: React route path (e.g., `/activity/my-plugin-log`)

**Optional parameters**:
- `icon`: Icon identifier (Dashicon slug, custom icon, or SVG)
- `nav_group`: Group name in sidebar (defaults to feature name, e.g., "Activity", "Groups")
- `order`: Numeric sort order within `nav_group` (default: 100)
- `badge`: Badge count or text to display next to label (e.g., `5` or `'New'`)
- `is_external`: If `true`, opens in new tab (for external URLs)
- `capability`: Required capability to see this nav item (default: `'manage_options'`)
- `is_active_callback`: Callback function that returns `true` if item should be visible

#### 12.3.4 Custom Navigation Groups

- **Creating new groups**:
  - Third-party plugins can create new `nav_group` names (e.g., "My Plugin Settings", "Reports", "Analytics").
  - All items with the same `nav_group` appear together in the sidebar.
  - Groups are automatically separated visually in the React UI.

- **Example**:
```php
// Create a new "Reports" group in Activity sidebar
bb_register_feature_nav_item( 'activity', array(
    'id'        => 'my_plugin_activity_report',
    'label'     => __( 'Activity Report', 'my-plugin' ),
    'route'     => '/activity/report',
    'nav_group' => 'Reports', // New group name
    'order'     => 10,
) );

bb_register_feature_nav_item( 'activity', array(
    'id'        => 'my_plugin_activity_analytics',
    'label'     => __( 'Activity Analytics', 'my-plugin' ),
    'route'     => '/activity/analytics',
    'nav_group' => 'Reports', // Same group - appears together
    'order'     => 20,
) );
```

#### 12.3.5 React Rendering (Automatic)

**No frontend code required**:
- The React `SideNavigation` component automatically reads all registered nav items from the REST API.
- Nav items are fetched via `GET /buddyboss/v1/features/{featureId}` which includes a `navigation` array.
- React renders all nav items in the left sidebar, grouped by `nav_group`.
- Clicking a nav item navigates to the specified `route`.

**React component structure**:
```javascript
// SideNavigation component automatically renders registered items
function SideNavigation({ featureId }) {
    const { navigation } = useFeatureData(featureId); // From REST API
    
    return (
        <nav>
            {Object.entries(groupBy(navigation, 'nav_group')).map(([group, items]) => (
                <NavGroup key={group} title={group}>
                    {items.map(item => (
                        <NavItem
                            key={item.id}
                            label={item.label}
                            icon={item.icon}
                            route={item.route}
                            badge={item.badge}
                            isActive={isCurrentRoute(item.route)}
                            onClick={() => navigate(item.route)}
                        />
                    ))}
                </NavGroup>
            ))}
        </nav>
    );
}
```

#### 12.3.6 Handling Custom Routes in React

**For custom nav items with custom routes**:
- Third-party plugins can provide their own React components for custom routes.
- Register the component via a filter or extension point:

```php
// In PHP
add_filter( 'buddyboss_admin_route_components', 'my_plugin_register_route_components' );
function my_plugin_register_route_components( $components ) {
    $components['/activity/my-plugin-log'] = array(
        'component' => 'MyPluginActivityLog', // React component name
        'bundle'    => 'my-plugin-admin', // JS bundle handle
    );
    return $components;
}
```

```javascript
// In React (my-plugin-admin.js)
// Register component in BuddyBoss admin app
window.bbAdminApp.registerRoute('/activity/my-plugin-log', MyPluginActivityLog);

// Or use lazy loading
const MyPluginActivityLog = lazy(() => import('./components/ActivityLog'));
```

#### 12.3.7 Complete Example: Side Panel Integration

**Full example of a third-party plugin adding side panel items**:

```php
<?php
/**
 * Plugin Name: My Groups Extension
 */

add_action( 'bb_register_features', 'my_groups_extension_register' );

function my_groups_extension_register() {
    // 1. Register a settings section (automatically appears in sidebar)
    bb_register_feature_section( 'groups', 'my_extension_settings', array(
        'title'       => __( 'My Extension Settings', 'my-extension' ),
        'description' => __( 'Configure My Extension for Groups.', 'my-extension' ),
        'nav_group'   => 'Extensions',
        'order'       => 10,
    ) );
    
    // Register fields for the section
    bb_register_feature_field( 'groups', 'my_extension_settings', array(
        'name'  => 'my_extension_enabled',
        'label' => __( 'Enable Extension', 'my-extension' ),
        'type'  => 'toggle',
    ) );
    
    // 2. Register a custom nav item for a list screen
    bb_register_feature_nav_item( 'groups', array(
        'id'        => 'my_extension_groups_list',
        'label'     => __( 'Extension Groups', 'my-extension' ),
        'route'     => '/groups/my-extension-list',
        'icon'      => 'dashicons-groups',
        'nav_group' => 'Extensions',
        'order'     => 20,
    ) );
    
    // 3. Register a custom nav item for a report screen
    bb_register_feature_nav_item( 'groups', array(
        'id'        => 'my_extension_report',
        'label'     => __( 'Groups Report', 'my-extension' ),
        'route'     => '/groups/my-extension-report',
        'icon'      => 'dashicons-chart-bar',
        'nav_group' => 'Reports', // New group
        'order'     => 10,
        'badge'     => 'New', // Optional badge
    ) );
}

// Register React route handler
add_filter( 'buddyboss_admin_route_components', 'my_extension_register_routes' );
function my_extension_register_routes( $components ) {
    $components['/groups/my-extension-list'] = array(
        'component' => 'MyExtensionGroupsList',
        'bundle'    => 'my-extension-admin',
    );
    $components['/groups/my-extension-report'] = array(
        'component' => 'MyExtensionReport',
        'bundle'    => 'my-extension-admin',
    );
    return $components;
}
```

**Result**:
- "My Extension Settings" appears in sidebar under "Extensions" group (from section registration).
- "Extension Groups" appears in sidebar under "Extensions" group (from nav item).
- "Groups Report" appears in sidebar under new "Reports" group (from nav item).
- All items are clickable and navigate to their respective React routes.
- React automatically renders all items in the left sidebar.

#### 12.3.8 REST API Support

**Navigation items are exposed via REST**:
- `GET /buddyboss/v1/features/{featureId}` returns:
```json
{
  "feature": { ... },
  "sections": [ ... ],
  "navigation": [
    {
      "id": "my_plugin_activity_log",
      "label": "My Plugin Activity Log",
      "route": "/activity/my-plugin-log",
      "icon": "dashicons-list-view",
      "nav_group": "Activity",
      "order": 200,
      "badge": null
    },
    ...
  ]
}
```

- React reads this data and renders the sidebar automatically.
- No manual React code needed for basic nav items.

### 12.4 PHP Hooks & Filters

The extensibility API provides hooks for plugins to customize behavior:

- **Feature registration hooks**:
  - `bb_before_register_features` – Fired before core features are registered.
  - `bb_register_features` – Fired after core features are registered (use this to add your features).
  - `bb_after_register_features` – Fired after all features are registered.
- **Section/Field registration hooks**:
  - `bb_register_feature_section_{$feature_id}` – Fired when registering sections for a specific feature.
  - `bb_register_feature_field_{$feature_id}_{$section_id}` – Fired when registering fields for a specific section.
- **REST API hooks** (for custom endpoints):
  - `buddyboss_rest_features_query_args` – Modify features list query.
  - `buddyboss_rest_feature_prepare_item` – Modify feature REST response.
  - `buddyboss_rest_feature_settings_update` – Hook into settings updates.
- **React UI customization hooks**:
  - `buddyboss_admin_feature_card_props` – Modify feature card props before rendering.
  - `buddyboss_admin_settings_field_render` – Override field rendering (advanced).

### 12.5 React UI Integration (Automatic)

The React admin shell automatically renders all registered features, sections, and fields:

- **No frontend code required**:
  - Third-party plugins do not need to write React components.
  - The shell reads from the Feature Registry via REST and renders generically.
- **Icon rendering**:
  - Icons are automatically rendered based on type:
    - Dashicons: Rendered via WordPress `Icon` component.
    - SVG/Image URLs: Rendered as `<img>` tags.
    - Registered custom icons: Resolved from icon registry and rendered appropriately.
    - Font icons: Rendered as `<span>` with CSS class.
    - React components: Lazy-loaded and rendered.
  - No manual icon rendering code needed - React handles all icon types automatically.
- **Field type mapping**:
  - The React shell maps field `type` to WordPress React components:
    - `toggle` → `ToggleControl`
    - `checkbox` → `CheckboxControl`
    - `text` → `TextControl`
    - `textarea` → `TextareaControl`
    - `select` → `SelectControl`
    - `radio` → `RadioControl`
- **Custom field rendering** (advanced):
  - Plugins can provide a custom React component via `render_component` in field args:
```php
bb_register_feature_field( 'activity', 'my_section', array(
    'name'            => 'my_custom_field',
    'label'           => __( 'Custom Field', 'my-plugin' ),
    'type'            => 'custom',
    'render_component' => 'MyPluginCustomField', // Must be registered in React
    'render_props'    => array( 'some' => 'data' ), // Props passed to component
) );
```
  - The plugin must enqueue a script that registers the component in the React app's component registry.

### 12.6 Settings Search Integration

All registered fields are automatically included in Settings Search:

- **Indexing**:
  - When a field is registered, it's added to the search index with:
    - Feature name, section title, field label.
    - Breadcrumb string (e.g., "Activity → My Plugin Settings → Enable Integration").
  - Search matches on field labels, descriptions, and option names.
- **Search results**:
  - Third-party fields appear in search results with:
    - Feature icon + name.
    - Section name + field name.
    - Clicking navigates to `/settings/{featureId}/{sectionId}` and highlights the field.

### 12.7 Custom Icon Registration Example

**Complete example of registering and using custom icons**:

```php
<?php
/**
 * Plugin Name: My Activity Extension
 */

// Register custom icons
add_action( 'bb_register_icons', 'my_extension_register_icons' );
function my_extension_register_icons() {
    // Register SVG icon
    bb_register_icon( 'my-extension-activity', array(
        'type'        => 'svg',
        'url'         => plugin_dir_url( __FILE__ ) . 'assets/icons/activity.svg',
        'width'       => 24,
        'height'      => 24,
        'description' => __( 'My Extension Activity Icon', 'my-extension' ),
    ) );
    
    // Register image icon
    bb_register_icon( 'my-extension-logo', array(
        'type' => 'image',
        'url'  => plugin_dir_url( __FILE__ ) . 'assets/images/logo.png',
    ) );
    
    // Register font icon
    bb_register_icon( 'my-extension-font', array(
        'type'  => 'font',
        'class' => 'my-extension-icon',
    ) );
}

// Use custom icon in feature registration
add_action( 'bb_register_features', 'my_extension_register_feature' );
function my_extension_register_feature() {
    bb_register_feature( 'my-extension', array(
        'label'              => __( 'My Extension', 'my-extension' ),
        'description'        => __( 'My custom extension feature.', 'my-extension' ),
        'icon'               => 'my-extension-activity', // Uses registered icon
        'category'           => 'add-ons',
        'license_tier'       => 'free',
        'is_active_callback' => 'my_extension_is_active',
        'settings_route'     => '/settings/my-extension',
    ) );
    
    // Or use SVG URL directly (no registration needed)
    bb_register_feature( 'my-other-feature', array(
        'label'  => __( 'My Other Feature', 'my-extension' ),
        'icon'   => plugin_dir_url( __FILE__ ) . 'assets/icons/other.svg',
        // ...
    ) );
}
```

### 12.8 Complete Example: Third-Party Plugin Integration

Here's a complete example of a third-party plugin extending Activity:

```php
<?php
/**
 * Plugin Name: My Activity Extension
 */

// Hook into feature registration
add_action( 'bb_register_features', 'my_activity_extension_register' );

function my_activity_extension_register() {
    // Add a new section to Activity
    bb_register_feature_section( 'activity', 'my_extension_settings', array(
        'title'       => __( 'My Extension Settings', 'my-extension' ),
        'description' => __( 'Configure how My Extension works with Activity.', 'my-extension' ),
        'nav_group'   => 'Extensions',
        'order'       => 10,
    ) );

    // Add fields to the section
    bb_register_feature_field( 'activity', 'my_extension_settings', array(
        'name'             => 'my_extension_activity_enabled',
        'label'            => __( 'Enable Extension', 'my-extension' ),
        'type'             => 'toggle',
        'description'      => __( 'Enable My Extension for Activity feed.', 'my-extension' ),
        'default'          => 1,
        'sanitize_callback' => 'intval',
    ) );

    bb_register_feature_field( 'activity', 'my_extension_settings', array(
        'name'             => 'my_extension_activity_mode',
        'label'            => __( 'Mode', 'my-extension' ),
        'type'             => 'select',
        'default'          => 'auto',
        'options'          => array(
            'auto' => __( 'Automatic', 'my-extension' ),
            'manual' => __( 'Manual', 'my-extension' ),
        ),
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    // Add a navigation item for a custom screen
    bb_register_feature_nav_item( 'activity', array(
        'label'     => __( 'Extension Log', 'my-extension' ),
        'route'     => '/activity/my-extension-log',
        'icon'      => 'dashicons-list-view',
        'nav_group' => 'Extensions',
        'order'     => 20,
    ) );
}

// Optional: Load PHP code only if feature is active
add_action( 'bb_feature_active_my_extension', 'my_extension_load_php' );
function my_extension_load_php() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/activity-integration.php';
}
```

### 12.9 Documentation & Developer Resources

- **API Reference**:
  - Full function signatures and parameter descriptions.
  - Field type reference with examples.
  - Hook and filter documentation.
- **Code Examples**:
  - Simple field registration examples.
  - Complete plugin integration examples.
  - Custom React component examples (advanced).
- **Best Practices**:
  - Naming conventions for feature IDs, section IDs, option names.
  - When to create a new feature vs. extending existing.
  - Performance considerations (lazy loading, code compartmentalization).

### 12.10 Summary: What Third-Party Plugins Get

- **New Features**:
  - Can register their own feature cards on Settings page.
  - Full integration with feature enable/disable toggles.
  - Automatic code compartmentalization (PHP only loads if active).
- **Extending Existing Features**:
  - Can add sections to Activity, Groups, etc.
  - Can add fields with various types (toggle, select, text, etc.).
  - Sections automatically appear in left navigation.
  - Fields automatically appear in React UI.
- **Search Integration**:
  - All registered fields are searchable via global Settings Search.
  - Search results link directly to the field.
- **Zero Frontend Code Required**:
  - React UI is generated automatically from registry data.
  - No need to write React components (unless custom rendering is needed).
- **Custom Icon Support**:
  - Can register custom icons (SVG, images, fonts, React components) via `bb_register_icon()`.
  - Icons automatically rendered in React UI.
  - Support for Dashicons, custom SVG URLs, registered icons, and more.
  - No manual icon rendering code needed.
- **Per-Group Settings**:
  - Can register per-group settings via `bb_register_group_setting()`.
  - Settings appear in Group Edit screen automatically.
  - Stored as group meta, accessible via existing `groups_get_groupmeta()` API.
  - Backward compatible with existing metabox-based per-group settings.
- **Backward Compatibility**:
  - Existing plugins using WordPress hooks and filters continue to work.
  - New registry API is additive, not replacing existing extensibility.
  - Existing group meta access (`groups_get_groupmeta()`) continues to work.

## 13. Add-On Plugin Integration (Platform Pro & Gamification)

### 13.1 Current Add-On Plugin Structure

**BuddyBoss Platform Pro** (`buddyboss-platform-pro/`):
- Contains multiple features/integrations:
  - **Integrations**: Zoom, Pusher, OneSignal, Tutor LMS, MemberPress LMS
  - **Features**: Polls, Reactions, Schedule Posts, Topics, SSO, Access Control
- Each integration/feature has its own directory under `includes/integrations/` or `includes/{feature}/`
- Currently registers settings via `bp-platform-settings-loader.php` which hooks into `bp_setup_components`
- Settings are added to existing BuddyBoss settings tabs (Activity, Groups, Profiles)

**BuddyBoss Gamification** (`buddyboss-gamification/`):
- Standalone add-on plugin
- Currently has its own admin menu: `add_submenu_page( 'buddyboss-platform', 'Gamification Settings', ... )`
- Uses React-based admin UI already (`includes/core/admin/`)
- Settings stored in `bb_gm_settings` option
- Has DRM integration (`BB_DRM_Registry::register_addon()`)

### 13.2 Integration Strategy for Add-On Plugins

**Goal**: Add-on plugins (Platform Pro, Gamification, and future add-ons) should register their features using the same Feature Registry API, appearing seamlessly in the new React Settings grid.

### 13.3 Platform Pro Integration

**Features to register from Platform Pro**:

1. **Zoom** (`includes/integrations/zoom/`)
2. **Pusher** (`includes/integrations/pusher/`)
3. **OneSignal** (`includes/integrations/onesignal/`)
4. **Tutor LMS** (`includes/integrations/tutorlms/`)
5. **MemberPress LMS** (`includes/integrations/meprlms/`)
6. **Polls** (`includes/polls/`)
7. **Reactions** (`includes/reactions/`)
8. **Schedule Posts** (`includes/schedule-posts/`)
9. **Topics** (`includes/topics/`)
10. **SSO** (`includes/sso/`)
11. **Access Control** (`includes/access-control/`)

**Implementation approach**:

- **Create feature registration file**:
  - Create `includes/bb-pro-feature-registry.php` in Platform Pro
  - Hook into `bb_register_features` action (fired after Platform core features)
  - Register each integration/feature as a separate feature card

- **Example registration for Zoom**:
```php
add_action( 'bb_register_features', 'bb_pro_register_zoom_feature', 20 );
function bb_pro_register_zoom_feature() {
    // Check if Zoom integration is available
    if ( ! class_exists( 'BB_Zoom_Integration' ) ) {
        return;
    }
    
    bb_register_feature( 'zoom', array(
        'label'              => __( 'Zoom', 'buddyboss-pro' ),
        'description'        => __( 'Integrate Zoom video conferencing with your community.', 'buddyboss-pro' ),
        'icon'               => 'dashicons-video-alt3', // Or custom SVG
        'category'           => 'integrations', // Appears in "BUDDYBOSS INTEGRATIONS" section
        'license_tier'       => 'pro', // 'free', 'pro', 'plus'
        'is_available_callback' => 'bb_pro_zoom_is_available', // Check if Zoom API keys configured
        'is_active_callback'     => 'bb_pro_zoom_is_active', // Check if enabled in options
        'settings_route'     => '/settings/zoom',
        'php_loader'         => 'bb_pro_zoom_load_php', // Load Zoom PHP code only if active
    ) );
    
    // Register Zoom settings sections
    bb_register_feature_section( 'zoom', 'zoom_general', array(
        'title'       => __( 'General Settings', 'buddyboss-pro' ),
        'description' => __( 'Configure Zoom API credentials and general settings.', 'buddyboss-pro' ),
        'nav_group'   => 'Zoom',
        'order'       => 10,
    ) );
    
    // Register Zoom settings fields
    bb_register_feature_field( 'zoom', 'zoom_general', array(
        'name'             => 'bb_zoom_api_key', // Existing option name
        'label'            => __( 'Zoom API Key', 'buddyboss-pro' ),
        'type'             => 'text',
        'description'      => __( 'Enter your Zoom API key.', 'buddyboss-pro' ),
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    
    // ... more fields
}
```

- **Migration of existing settings**:
  - Audit all existing Platform Pro settings files:
    - `includes/platform-settings/activity/class-bb-pro-activity-settings.php`
    - `includes/platform-settings/groups/class-bb-pro-groups-settings.php`
    - `includes/platform-settings/profiles/class-bb-pro-profiles-settings.php`
  - Map existing settings to Feature Registry:
    - Settings that extend Activity → Register as sections/fields under `'activity'` feature
    - Settings that extend Groups → Register as sections/fields under `'groups'` feature
    - Standalone features (Zoom, Pusher, etc.) → Register as separate features

- **Code compartmentalization**:
  - Each feature's `php_loader` callback only loads if `is_active_callback()` returns true
  - Example:
```php
function bb_pro_zoom_load_php() {
    require_once bb_platform_pro()->integration_dir . '/zoom/bb-zoom-loader.php';
}
```
  - This ensures disabled features don't load any code

- **Backward compatibility**:
  - Existing option names remain unchanged
  - Existing hooks/filters continue to work
  - Settings continue to be stored in same options
  - Old settings pages can redirect to new React routes

### 13.4 Gamification Integration

**Current state**:
- Has its own React admin UI already
- Uses `add_submenu_page( 'buddyboss-platform', 'Gamification Settings', ... )`
- Settings stored in `bb_gm_settings` option

**Migration approach**:

- **Option A: Register as feature in Settings grid** (Recommended):
  - Register Gamification as a feature card in Settings grid
  - Keep existing React UI but mount it within new admin shell
  - Or migrate settings to Feature Registry and use generic React UI

- **Option B: Keep separate menu item** (During transition):
  - Keep existing menu item during transition
  - Gradually migrate settings to Feature Registry
  - Eventually remove separate menu and use Settings grid only

**Recommended: Hybrid approach**:

1. **Register as feature**:
```php
add_action( 'bb_register_features', 'bb_gm_register_feature', 20 );
function bb_gm_register_feature() {
    bb_register_feature( 'gamification', array(
        'label'              => __( 'Gamification', 'buddyboss-gamification' ),
        'description'        => __( 'Reward members with points, ranks, and achievements.', 'buddyboss-gamification' ),
        'icon'               => 'dashicons-awards',
        'category'           => 'add-ons', // Appears in "BUDDYBOSS ADD-ONS" section
        'license_tier'       => 'pro',
        'is_available_callback' => 'bb_gm_is_license_valid',
        'is_active_callback'     => 'bb_gm_is_active', // Check if enabled
        'settings_route'     => '/settings/gamification',
        'php_loader'         => 'bb_gm_load_php', // Load Gamification code only if active
    ) );
}
```

2. **Migrate settings to Feature Registry**:
   - Map existing `bb_gm_settings` structure to Feature Registry sections/fields
   - Register sections: General, Points, Manual Award, etc.
   - Register fields with existing option names

3. **React UI options**:
   - **Option 1**: Use generic React UI from Feature Registry (simpler, consistent)
   - **Option 2**: Keep custom React UI but mount within new admin shell (more work, preserves custom UI)
   - **Recommended**: Option 1 for consistency, but support custom React components if needed

4. **Menu item handling**:
   - Keep existing menu item initially (backward compatibility)
   - Menu item can redirect to `/settings/gamification` in React shell
   - Eventually remove separate menu item once fully migrated

### 13.5 Changes Required in Add-On Plugins

**For BuddyBoss Platform Pro**:

1. **Create feature registration file**:
   - `includes/bb-pro-feature-registry.php`
   - Register all integrations and features using `bb_register_feature()`
   - Hook into `bb_register_features` action

2. **Migrate existing settings**:
   - Map settings from `platform-settings/` to Feature Registry
   - Register sections/fields for Activity, Groups, Profiles extensions
   - Register standalone features (Zoom, Pusher, etc.) as separate features

3. **Update code loading**:
   - Implement `php_loader` callbacks for each feature
   - Ensure code only loads if feature is active (code compartmentalization)

4. **Update admin menu callbacks** (if any):
   - Change menu callbacks to render React root or redirect to React routes
   - Maintain backward compatibility with existing URLs

5. **Testing**:
   - Verify all features appear in Settings grid
   - Verify settings save/load correctly
   - Verify code compartmentalization works
   - Verify backward compatibility

**For BuddyBoss Gamification**:

1. **Register feature**:
   - Add feature registration in main plugin file or separate file
   - Hook into `bb_register_features` action

2. **Migrate settings**:
   - Map `bb_gm_settings` structure to Feature Registry
   - Register sections and fields

3. **React UI decision**:
   - Choose: Generic UI or keep custom UI
   - If keeping custom UI, ensure it mounts within new admin shell

4. **Menu item**:
   - Keep existing menu item initially
   - Update callback to redirect to React route or render within shell

5. **Testing**:
   - Verify feature appears in Settings grid
   - Verify settings work correctly
   - Verify code compartmentalization

### 13.6 Backward Compatibility for Add-On Plugins

**Critical**: Ensure add-on plugins continue to work during and after migration, **even if they haven't been updated** to use the new Feature Registry API.

**See section 13.9 for detailed backward compatibility implementation** covering:
- Auto-detection of old-style settings (BP_Admin_Tab, WordPress Settings API)
- Compatibility layer that converts old settings to Feature Registry format
- React UI support for rendering legacy settings
- Coexistence of old and new systems

**Key Compatibility Guarantees**:

- **Option names**:
  - All existing option names remain unchanged
  - Feature Registry reads/writes same options

- **Hooks and filters**:
  - All existing hooks continue to work
  - New hooks added for Feature Registry integration
  - No breaking changes to existing hooks

- **Admin URLs**:
  - Existing URLs continue to work (via URL mapping, see section 6.3.1)
  - `?page=bb-gamification-settings` → Routes to `/settings/gamification`
  - Old URLs redirect or map to new React routes

- **Code loading**:
  - Existing code loading mechanisms continue to work
  - New `php_loader` callbacks are additive
  - Can use both old and new mechanisms during transition

- **Settings access**:
  - Existing `get_option()` / `bp_get_option()` calls continue to work
  - Feature Registry doesn't change how options are stored
  - Settings remain accessible via existing APIs

- **Old Settings System** (See 13.9 for details):
  - Old-style settings (BP_Admin_Tab, add_section, add_field) auto-detected
  - Appear in React UI automatically via compatibility layer
  - No add-on plugin updates required initially
  - Gradual migration path available

### 13.7 Migration Timeline for Add-On Plugins

**Phase 1: Platform Pro core features** (Weeks 1-2):
- Register Zoom, Pusher, OneSignal as features
- Test in Settings grid
- Verify settings save/load

**Phase 2: Platform Pro remaining features** (Weeks 3-4):
- Register Polls, Reactions, Schedule Posts, Topics
- Register SSO, Access Control
- Migrate Activity/Groups/Profiles extension settings

**Phase 3: Gamification** (Weeks 5-6):
- Register Gamification feature
- Migrate settings to Feature Registry
- Decide on React UI approach
- Test thoroughly

**Phase 4: Cleanup** (Week 7):
- Remove old settings page code (if applicable)
- Update documentation
- Final testing

### 13.8 Testing Checklist for Add-On Plugins

- [ ] All features appear in Settings grid under correct category
- [ ] Feature cards show correct status (Active/Inactive)
- [ ] Clicking feature card navigates to settings page
- [ ] All settings sections appear in left navigation
- [ ] All settings fields render correctly
- [ ] Settings save/load correctly (same option names)
- [ ] Code compartmentalization works (disabled features don't load code)
- [ ] Existing URLs continue to work
- [ ] Existing hooks/filters continue to work
- [ ] License checks work correctly
- [ ] Settings search includes add-on features
- [ ] Third-party plugins extending add-on features continue to work

### 13.9 Backward Compatibility: Old Settings System Support

**Critical Scenario**: BuddyBoss Platform is updated to new React version, but add-on plugins (Platform Pro, Gamification, third-party) still use the **old settings system** (BP_Admin_Tab, add_section(), add_field(), etc.).

**Requirement**: Old-style settings must continue to work and appear in the new React UI without requiring add-on plugin updates.

#### 13.9.1 Compatibility Layer Architecture

**Auto-Detection & Migration Layer**:
- The Feature Registry will automatically detect and include old-style settings
- Old settings are converted to Feature Registry format on-the-fly
- No changes required in add-on plugins initially

**Implementation**:

1. **Old Settings Detection**:
   - Feature Registry scans for existing `BP_Admin_Tab` instances
   - Detects settings registered via `add_settings_section()` and `register_setting()`
   - Maps old settings to Feature Registry structure

2. **Auto-Registration**:
   - When Feature Registry initializes, it calls a compatibility layer
   - Compatibility layer reads existing WordPress Settings API registrations
   - Converts them to Feature Registry format automatically

3. **Dual Rendering Support**:
   - React UI can render both new Feature Registry settings AND old-style settings
   - Old settings appear in React UI with same look/feel
   - Settings save/load using existing WordPress Settings API

#### 13.9.2 Compatibility Layer Implementation

**Create `class-bb-feature-registry-compat.php`**:

```php
<?php
/**
 * Feature Registry Compatibility Layer
 * 
 * Automatically detects and includes old-style settings (BP_Admin_Tab, WordPress Settings API)
 * so add-on plugins don't need immediate updates.
 */

class BB_Feature_Registry_Compat {
    
    /**
     * Auto-detect old-style settings and register them in Feature Registry
     */
    public static function auto_register_old_settings() {
        global $wp_settings_sections, $wp_settings_fields;
        
        // Get all registered settings sections
        if ( empty( $wp_settings_sections ) ) {
            return;
        }
        
        // Look for BuddyBoss-related settings pages
        $bb_pages = array( 'bp-settings', 'bp-components', 'bp-integrations' );
        
        foreach ( $bb_pages as $page ) {
            if ( ! isset( $wp_settings_sections[ $page ] ) ) {
                continue;
            }
            
            // Determine feature ID from page
            $feature_id = self::map_page_to_feature( $page );
            
            foreach ( $wp_settings_sections[ $page ] as $section_id => $section ) {
                // Register section in Feature Registry
                bb_register_feature_section( $feature_id, $section_id, array(
                    'title'       => $section['title'],
                    'description' => isset( $section['description'] ) ? $section['description'] : '',
                    'nav_group'   => self::determine_nav_group( $section_id ),
                    'order'       => isset( $section['order'] ) ? $section['order'] : 100,
                    'is_legacy'   => true, // Flag as legacy for special handling
                ) );
                
                // Register fields in this section
                if ( isset( $wp_settings_fields[ $page ][ $section_id ] ) ) {
                    foreach ( $wp_settings_fields[ $page ][ $section_id ] as $field_id => $field ) {
                        bb_register_feature_field( $feature_id, $section_id, array(
                            'name'             => $field_id,
                            'label'            => isset( $field['title'] ) ? $field['title'] : $field_id,
                            'type'             => self::map_field_type( $field ),
                            'description'      => isset( $field['description'] ) ? $field['description'] : '',
                            'default'          => isset( $field['default'] ) ? $field['default'] : '',
                            'sanitize_callback' => isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : 'sanitize_text_field',
                            'is_legacy'        => true, // Flag as legacy
                        ) );
                    }
                }
            }
        }
    }
    
    /**
     * Map old settings page to feature ID
     */
    private static function map_page_to_feature( $page ) {
        $map = array(
            'bp-settings' => 'activity', // Default, will be refined
            'bp-components' => 'components',
            'bp-integrations' => 'integrations',
        );
        return isset( $map[ $page ] ) ? $map[ $page ] : 'general';
    }
    
    /**
     * Determine navigation group from section ID
     */
    private static function determine_nav_group( $section_id ) {
        // Map common section IDs to nav groups
        if ( strpos( $section_id, 'activity' ) !== false ) {
            return 'Activity';
        } elseif ( strpos( $section_id, 'group' ) !== false ) {
            return 'Groups';
        } elseif ( strpos( $section_id, 'access' ) !== false ) {
            return 'Access Control';
        }
        return 'General';
    }
    
    /**
     * Map old field type to new field type
     */
    private static function map_field_type( $field ) {
        if ( isset( $field['type'] ) ) {
            $type_map = array(
                'checkbox' => 'toggle',
                'text'     => 'text',
                'textarea' => 'textarea',
                'select'   => 'select',
                'radio'    => 'radio',
            );
            return isset( $type_map[ $field['type'] ] ) ? $type_map[ $field['type'] ] : 'text';
        }
        return 'text';
    }
}
```

**Integration into Feature Registry**:

```php
// In class-bb-feature-registry.php
class BB_Feature_Registry {
    
    public function __construct() {
        // ... existing code ...
        
        // After core features are registered, auto-detect old settings
        add_action( 'bb_after_register_features', array( 'BB_Feature_Registry_Compat', 'auto_register_old_settings' ), 5 );
    }
}
```

#### 13.9.3 React UI Support for Legacy Settings

**Legacy Settings Rendering**:
- React UI detects `is_legacy: true` flag on sections/fields
- Legacy settings use WordPress Settings API for save/load
- React UI renders legacy fields using same components as new fields
- Save operations call existing `update_option()` / `bp_update_option()` functions

**Example React component handling**:

```javascript
// In React settings screen component
function SettingsField( { field } ) {
    const isLegacy = field.is_legacy || false;
    
    // Use same React components for both new and legacy fields
    if ( field.type === 'toggle' ) {
        return <ToggleControl
            label={ field.label }
            checked={ getOptionValue( field.name, field.default ) }
            onChange={ ( value ) => saveOption( field.name, value, isLegacy ) }
        />;
    }
    // ... other field types
}

function saveOption( name, value, isLegacy ) {
    if ( isLegacy ) {
        // Use WordPress Settings API
        wp.apiFetch( {
            path: '/wp/v2/settings',
            method: 'POST',
            data: { [name]: value }
        } );
    } else {
        // Use new Feature Registry REST API
        wp.apiFetch( {
            path: '/buddyboss/v1/features/settings',
            method: 'POST',
            data: { [name]: value }
        } );
    }
}
```

#### 13.9.4 Old Settings Pages Support

**Coexistence Strategy**:
- Old PHP-rendered settings pages continue to work
- Can be accessed via direct URL: `?page=bp-settings&tab=bp-activity`
- Old pages can optionally redirect to React UI
- Or old pages can render within React shell as fallback

**Menu Callback Handling**:

```php
// In class-bp-admin.php
public function register_admin_pages() {
    // Old-style menu registration
    add_submenu_page(
        'buddyboss-platform',
        __( 'Settings', 'buddyboss' ),
        __( 'Settings', 'buddyboss' ),
        'manage_options',
        'bp-settings',
        array( $this, 'settings_page_callback' )
    );
}

public function settings_page_callback() {
    // Check if new React admin is enabled
    if ( apply_filters( 'bb_use_new_admin', true ) ) {
        // Render React shell
        echo '<div id="bb-admin-app"></div>';
        
        // React app will detect ?page=bp-settings and route accordingly
        // Legacy settings will be auto-detected and rendered
    } else {
        // Fallback to old PHP-rendered page
        require_once $this->admin_dir . 'settings/bp-admin-settings.php';
    }
}
```

#### 13.9.5 BP_Admin_Tab Compatibility

**Support for BP_Admin_Tab class**:
- `BP_Admin_Tab` uses WordPress Settings API under the hood
- Compatibility layer reads `BP_Admin_Tab` registrations
- Converts tabs → sections, fields → fields automatically

**Example detection**:

```php
// In compatibility layer
public static function detect_bp_admin_tabs() {
    // BP_Admin_Tab stores data in global arrays or options
    // We can detect registered tabs and convert them
    
    if ( class_exists( 'BP_Admin_Tab' ) ) {
        // Access BP_Admin_Tab's internal data
        // This may require adding a getter method to BP_Admin_Tab
        $tabs = BP_Admin_Tab::get_all_tabs(); // Hypothetical method
        
        foreach ( $tabs as $tab_id => $tab ) {
            $feature_id = self::map_tab_to_feature( $tab_id );
            
            // Register as section
            bb_register_feature_section( $feature_id, $tab_id, array(
                'title'       => $tab['title'],
                'description' => isset( $tab['description'] ) ? $tab['description'] : '',
                'is_legacy'   => true,
            ) );
            
            // Register fields from tab
            if ( isset( $tab['fields'] ) ) {
                foreach ( $tab['fields'] as $field_id => $field ) {
                    bb_register_feature_field( $feature_id, $tab_id, array(
                        'name'  => $field_id,
                        'label' => $field['label'],
                        'type'  => $field['type'],
                        'is_legacy' => true,
                    ) );
                }
            }
        }
    }
}
```

#### 13.9.6 Migration Path for Add-On Plugins

**Gradual Migration**:
- **Phase 1**: Add-on plugins continue using old system, auto-detected by compatibility layer
- **Phase 2**: Add-on plugins can optionally migrate to new Feature Registry API
- **Phase 3**: Eventually deprecate old system (with long deprecation period)

**No Breaking Changes**:
- Old system continues to work indefinitely
- Add-on plugins can migrate at their own pace
- Both systems can coexist
- No forced updates required

#### 13.9.7 Testing Backward Compatibility

**Test Scenarios**:
1. **Platform updated, Platform Pro NOT updated**:
   - Platform Pro settings should appear in React UI
   - Settings should save/load correctly
   - All functionality should work

2. **Platform updated, Gamification NOT updated**:
   - Gamification settings should appear in React UI
   - Existing React UI should continue to work
   - Or settings should auto-detect and render

3. **Platform updated, third-party plugin NOT updated**:
   - Third-party settings should appear in React UI
   - Settings should save/load correctly

4. **Mixed environment**:
   - Some plugins using new system, some using old
   - Both should work simultaneously
   - No conflicts or errors

#### 13.9.8 Summary: Backward Compatibility Guarantees

**What Works Without Add-On Updates**:
- ✅ Old-style settings (BP_Admin_Tab, add_section, add_field) auto-detected
- ✅ Settings appear in new React UI automatically
- ✅ Settings save/load using existing WordPress Settings API
- ✅ Old admin URLs continue to work
- ✅ Old menu items continue to work
- ✅ All existing hooks/filters continue to work
- ✅ No breaking changes to add-on plugins

**Migration Benefits** (when add-ons DO update):
- ✅ Better integration with Settings grid
- ✅ Better categorization and organization
- ✅ Settings search includes their features
- ✅ Code compartmentalization support
- ✅ More control over UI/UX

**Timeline**:
- **Immediate**: Old system works via compatibility layer
- **Short-term**: Add-ons can migrate gradually (weeks/months)
- **Long-term**: Old system deprecated but still supported (years)

## 14. Migration & Refactor Strategy (Remove & Replace)

You want to **avoid duplication** and ensure old admin code is properly replaced while maintaining full backward compatibility.

### 14.1 Phase 1 – Introduce Registry & REST in Parallel (Foundation)

**Goal**: Build the infrastructure without breaking existing functionality.

- **Implement Feature Registry**:
  - Create `class-bb-feature-registry.php` with public APIs.
  - Create base feature classes for Activity, Groups, etc.
  - Register core features from existing settings files.
- **Implement REST API layer**:
  - Create `BB_REST_Features_Controller` for `/buddyboss/v1/features`.
  - Create `BB_REST_Settings_Search_Controller` for search.
  - Ensure all endpoints enforce `manage_options` capability.
- **Populate registry from existing settings**:
  - Read from `bp-core/admin/settings/*.php` files.
  - Map existing `add_section()` / `add_field()` calls to registry.
  - **Preserve all option names exactly as-is**.
- **Keep existing PHP UI intact**:
  - All existing admin screens continue to work.
  - No user-facing changes yet.
- **Testing checkpoint**:
  - Verify registry can read all existing settings.
  - Verify REST endpoints return correct data.
  - Verify no regressions in existing admin screens.

### 14.2 Phase 2 – Build React Admin Shell (UI Foundation)

**Goal**: Create the new React shell that can coexist with old admin.

- **Update admin menu callbacks**:
  - Modify `src/bp-core/classes/class-bp-admin.php`:
    - Change main BuddyBoss menu callback to render React root:
```php
// Old callback
add_menu_page( 'BuddyBoss', 'BuddyBoss', 'manage_options', 'buddyboss-platform', 'bp_core_admin_settings' );

// New callback
add_menu_page( 'BuddyBoss', 'BuddyBoss', 'manage_options', 'buddyboss-platform', 'bb_render_admin_shell' );

function bb_render_admin_shell() {
    // Output React root
    echo '<div id="bb-admin-app"></div>';
    
    // Enqueue shell bundle
    $asset = require plugin_dir_path( __FILE__ ) . '../admin/js/admin-app/build/shell.asset.php';
    wp_enqueue_script(
        'bb-admin-shell',
        plugin_dir_url( __FILE__ ) . '../admin/js/admin-app/build/shell.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    wp_enqueue_style(
        'bb-admin-shell',
        plugin_dir_url( __FILE__ ) . '../admin/js/admin-app/build/shell.css',
        array(),
        $asset['version']
    );
    
    // Localize script with initial data
    wp_localize_script( 'bb-admin-shell', 'bbAdminData', array(
        'apiRoot' => rest_url( 'buddyboss/v1/' ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
        'routes'  => array(
            'dashboard' => '/dashboard',
            'settings'  => '/settings',
            // ... other routes
        ),
    ) );
}
```
    - Update submenu callbacks similarly:
      - `bp-components` → Render React shell or redirect to `#/settings?tab=components`
      - `bp-settings` → Render React shell or redirect to `#/settings`
      - `bp-integrations` → Render React shell or redirect to `#/integrations`
  - **Legacy URL handling**:
    - Detect old query params (`?page=bp-settings&tab=bp-activity`)
    - Map to React routes automatically
    - Optionally clean up URL after navigation

- **Build core React shell**:
  - Dashboard screen (matches Figma).
  - Settings feature list grid (matches Figma).
  - Basic routing infrastructure.
  - Top header with search.
- **Mounting strategy**:
  - Update `BuddyBoss` main menu callback to render `<div id="bb-admin-app"></div>`.
  - Enqueue shell bundle with WordPress React dependencies.
  - Use feature flag `BB_USE_NEW_ADMIN` (constant or filter) to toggle:
    - `true`: Render React shell.
    - `false`: Render old PHP admin (backward compatibility).
- **URL routing & legacy compatibility**:
  - Implement URL mapping for legacy query params (see section 6.3.1).
  - React router detects `?page=bp-settings&tab=bp-activity` and routes to `/settings/activity`.
  - All existing bookmarks and direct links continue to work.
  - Test all legacy URL patterns map correctly to React routes.
- **Data integration**:
  - Shell reads from `/buddyboss/v1/features` for Settings grid.
  - Shell reads from `/buddyboss/v1/features/{id}` for feature detail pages.
- **Testing checkpoint**:
  - Verify shell loads without errors.
  - Verify Dashboard displays correctly.
  - Verify Settings grid shows all features.
  - Verify feature flag toggle works.
  - **Verify legacy URLs work**: Test `?page=bp-settings&tab=bp-activity` routes correctly.
  - **Verify deep linking works**: Test section-level and field-level deep links.
  - Run internal QA with feature flag enabled.

### 14.3 Phase 3 – Port Activity & Groups Settings (Feature Migration)

**Goal**: Migrate Activity and Groups settings to React while maintaining compatibility.

- **Activity Settings migration**:
  - Map all options from `bp-admin-setting-activity.php` to registry.
  - Build React Activity settings screen at `/settings/activity`.
  - Implement left navigation matching Figma.
  - Test all field types (toggle, select, text, etc.).
  - Verify option names unchanged.
- **Groups Settings migration**:
  - Map all options from `bp-admin-setting-groups.php` to registry.
  - Build React Groups settings screen at `/settings/groups`.
  - Implement left navigation matching Figma.
  - Test all field types.
  - Verify option names unchanged.
- **Compatibility layer**:
  - Keep old PHP settings pages accessible via direct URL (`?page=bp-settings&tab=activity`) during transition.
  - When React shell is active, legacy URLs automatically route to React (see section 6.3.1).
  - Old PHP pages can show a deprecation notice if accessed directly (when feature flag is off).
- **URL compatibility testing**:
  - Test all existing URL patterns:
    - `?page=bp-settings&tab=bp-activity` → Routes to `/settings/activity`.
    - `?page=bp-settings&tab=bp-groups` → Routes to `/settings/groups`.
    - `?page=bp-activity` → Routes to `/activity/all`.
    - `?page=bp-groups` → Routes to `/groups/all`.
    - `?page=bp-groups&gid={id}&action=edit` → Routes to `/groups/{id}/edit`.
  - Verify bookmarks continue to work.
  - Verify browser back/forward buttons work.
  - Verify URL sharing works (both old and new formats).
- **Testing checkpoint**:
  - Verify all Activity settings save correctly.
  - Verify all Groups settings save correctly.
  - Verify third-party plugins reading/writing options still work.
  - Verify no data loss or corruption.
  - **Verify all legacy URLs route correctly to React screens**.
  - **Verify deep linking works** (section-level, field-level).
  - Beta testing with select users.

### 14.4 Phase 4 – Port Activity & Groups Admin Lists (List Screens)

**Goal**: Replace list-table-based admin screens with React.

- **All Activity list**:
  - Build `BB_REST_Activity_Controller` at `/buddyboss/v1/activity`.
  - Build React screen at `/activity/all`.
  - Implement filters, search, pagination, bulk actions.
  - Test all operations (edit, delete, spam, restore).
- **All Groups list**:
  - Build `BB_REST_Groups_Controller` at `/buddyboss/v1/groups`.
  - Build React screen at `/groups/all`.
  - Implement filters, search, pagination, bulk actions.
  - Test all operations.
- **Update menu callbacks**:
  - Change `bp-activity` submenu to render React root or redirect to `/activity/all`.
  - Change `bp-groups` submenu to render React root or redirect to `/groups/all`.
- **Compatibility layer**:
  - Keep old list-table screens accessible via direct URL.
  - Show deprecation notice.
- **Testing checkpoint**:
  - Verify all list operations work correctly.
  - Verify bulk actions work.
  - Verify filters and search work.
  - Verify no regressions in functionality.

### 14.5 Phase 5 – Port Group Edit/Create & Group-Specific Settings

**Goal**: Migrate group edit screens and handle per-group meta fields/settings.

- **Group Create/Edit forms**:
  - Build React forms at `/groups/create` and `/groups/{id}/edit`.
  - Implement all existing edit metabox functionality:
    - Group details (name, description, status, type, parent).
    - Group settings (privacy, invitations, subscriptions).
    - Members management (add/remove, change roles).
  - Test all operations.
- **Group-specific settings/meta fields migration**:
  - **Identify existing group meta fields**:
    - Audit all `groups_update_groupmeta()` / `groups_get_groupmeta()` calls.
    - Identify which meta fields are settings (vs. internal data).
    - Document all group-specific settings currently stored as meta.
  - **Registry API for per-group settings**:
    - Extend Feature Registry to support **per-group settings**:
      - `bb_register_group_setting( string $setting_id, array $args )`:
        - Registers a setting that appears in the Group Edit screen.
        - Stored as group meta (via `groups_update_groupmeta()`).
        - Example:
```php
bb_register_group_setting( 'my_plugin_group_setting', array(
    'label'            => __( 'My Plugin Setting', 'my-plugin' ),
    'type'             => 'toggle', // or 'text', 'select', etc.
    'description'      => __( 'Enable My Plugin for this group.', 'my-plugin' ),
    'default'          => 0,
    'sanitize_callback' => 'intval',
    'section'          => 'group_settings', // Which section in edit screen
    'order'            => 100,
) );
```
    - REST endpoint: `GET /buddyboss/v1/groups/{id}/settings` returns all registered per-group settings.
    - REST endpoint: `POST /buddyboss/v1/groups/{id}/settings` updates per-group settings.
  - **Migrate existing group meta fields**:
    - Register all existing group-specific settings via new API.
    - Ensure they appear in React Group Edit screen.
    - Maintain backward compatibility with existing `groups_get_groupmeta()` calls.
  - **Third-party plugin compatibility**:
    - Plugins using `bp_groups_admin_meta_boxes` hook can:
      - Continue using metaboxes (old UI) during transition.
      - Migrate to `bb_register_group_setting()` for React UI.
      - Or provide both (metabox for old UI, registry for new UI).
    - Create migration guide for third-party developers.
- **Group Types & Navigation**:
  - Build React screens for group types management.
  - Build React screen for group navigation designer.
- **Testing checkpoint**:
  - Verify group create/edit works correctly.
  - Verify all per-group settings save/load correctly.
  - Verify group meta fields migrate correctly.
  - Verify third-party metaboxes still work (if not migrated).
  - Verify backward compatibility with existing group meta access.

### 14.6 Phase 6 – Remove Old Admin PHP UIs (Cleanup)

**Goal**: Remove deprecated code once React versions are stable.

- **Deprecation period**:
  - Mark old PHP admin screens as deprecated (add `@deprecated` tags).
  - Show admin notices pointing users to new interface.
  - Keep old screens functional for 1-2 minor versions.
- **Removal checklist**:
  - ✅ React versions fully tested and stable.
  - ✅ All functionality verified in React.
  - ✅ Third-party plugins have migration path.
  - ✅ Documentation updated.
- **Files to remove/move to `deprecated/`**:
  - `src/bp-activity/bp-activity-admin.php` (list table UI only; keep CRUD logic).
  - `src/bp-groups/bp-groups-admin.php` (list table + metabox UI only; keep CRUD logic).
  - Old settings page templates in `bp-core/admin/settings/` (after all features migrated).
  - Old admin callback functions (if not used elsewhere).
- **Keep intact**:
  - All low-level PHP logic (`BP_Activity_Activity`, `groups_create_group()`, etc.).
  - All public APIs and hooks.
  - All option/meta storage mechanisms.
  - All REST endpoints (they become the primary interface).

### 14.7 Phase 7 – Extend to Remaining Features (Complete Migration)

**Goal**: Migrate all remaining features to the new architecture.

- **Remaining features to migrate**:
  - Messages settings and admin.
  - Media/Video settings.
  - Search settings.
  - Performance settings.
  - Notifications settings.
  - XProfile settings.
  - Moderation settings.
  - Integrations settings.
- **Process for each feature**:
  - Map existing settings to registry.
  - Build React settings screen.
  - Build admin list screens if applicable.
  - Test thoroughly.
  - Remove old PHP UI.
- **Final state**:
  - All settings live in Feature Registry.
  - All admin screens are React-based.
  - All data access via REST API.
  - Old PHP admin code completely removed.

### 14.8 Compatibility & Backward Compatibility Strategy

**Critical**: Ensure no breaking changes for third-party plugins.

- **URL compatibility**:
  - **All existing URLs continue to work**:
    - `?page=bp-settings&tab=bp-activity` → Automatically routes to React `/settings/activity`.
    - `?page=bp-settings&tab=bp-groups` → Automatically routes to React `/settings/groups`.
    - `?page=bp-activity` → Automatically routes to React `/activity/all`.
    - `?page=bp-groups` → Automatically routes to React `/groups/all`.
    - All other existing `?page=...&tab=...` patterns continue to work.
  - **Deep linking support**:
    - Section-level: `?page=bp-settings&tab=bp-activity&section=activity_comments` → `/settings/activity/activity_comments`.
    - Field-level: Search results and direct links to specific fields work.
  - **Bookmark compatibility**:
    - All existing bookmarks continue to work.
    - Users can bookmark new React routes as well.
  - **Third-party plugin links**:
    - Plugins linking to `?page=bp-settings&tab=bp-activity` continue to work.
    - No changes needed in third-party code.
- **Option names**:
  - **Never change** existing option names.
  - All new React components read/write same options.
  - Feature Registry is a thin abstraction layer.
- **Hooks and filters**:
  - **Preserve all existing hooks** (`bp_activity_admin_load`, `bp_groups_admin_meta_boxes`, etc.).
  - Add new hooks for REST API and React UI.
  - Document which hooks are deprecated vs. active.
- **Group meta fields**:
  - **Maintain backward compatibility**:
    - Existing `groups_get_groupmeta()` / `groups_update_groupmeta()` calls continue to work.
    - New registry API is additive, not replacing.
    - Plugins can use either old meta API or new registry API.
  - **Migration path for third-party plugins**:
    - Plugins using `bp_groups_admin_meta_boxes` can:
      1. Continue using metaboxes (works in old UI).
      2. Register via `bb_register_group_setting()` (works in new React UI).
      3. Do both during transition period.
- **Public APIs**:
  - All existing public functions remain unchanged.
  - New registry APIs are additions, not replacements.
- **Data storage**:
  - No database schema changes.
  - No migration scripts needed.
  - All data remains in same tables/options.

### 14.9 Testing Strategy

**Comprehensive testing at each phase**:

- **Unit tests**:
  - Feature Registry registration.
  - REST endpoint responses.
  - Option read/write operations.
- **Integration tests**:
  - React components render correctly.
  - Settings save/load correctly.
  - List screens display and filter correctly.
- **Compatibility tests**:
  - Third-party plugins continue to work.
  - Existing hooks/filters still fire.
  - Option access still works.
- **User acceptance testing**:
  - Beta testing with select users.
  - Gather feedback on UI/UX.
  - Fix issues before full rollout.
- **Performance tests**:
  - Verify code compartmentalization works (disabled features don't load code).
  - Verify React bundle sizes are reasonable.
  - Verify REST endpoint performance.

### 14.10 Rollback Plan

**If issues arise, ability to rollback**:

- **Feature flag**:
  - `BB_USE_NEW_ADMIN` constant/filter allows instant rollback.
  - Set to `false` to revert to old PHP admin.
- **Code structure**:
  - Old PHP admin code remains until Phase 6.
  - Can be re-enabled if needed.
- **Database**:
  - No schema changes, so no rollback needed.
  - Options remain unchanged.

### 14.11 Documentation Updates

**Update documentation at each phase**:

- **Developer documentation**:
  - Feature Registry API reference.
  - REST API documentation.
  - Migration guide for third-party plugins.
  - Examples for extending features.
- **User documentation**:
  - New admin interface guide.
  - Settings location changes.
  - Feature enable/disable guide.
- **Release notes**:
  - Document changes in each release.
  - Migration notes for users.
  - Deprecation notices.

### 14.12 Summary: Migration Phases Overview

| Phase | Focus | Risk Level | Duration Estimate |
|-------|-------|------------|-------------------|
| 1 | Registry + REST foundation | Low | 2-3 weeks |
| 2 | React shell + Dashboard | Medium | 3-4 weeks |
| 3 | Activity/Groups settings | Medium | 4-5 weeks |
| 4 | Activity/Groups lists | Medium | 3-4 weeks |
| 5 | Group edit + per-group settings | High | 4-5 weeks |
| 6 | Remove old PHP UI | Low | 1-2 weeks |
| 7 | Remaining features | Medium | 8-10 weeks |

**Total estimated duration**: 6-8 months (depending on team size and feature scope).

**Key principles**:
- ✅ Never break backward compatibility.
- ✅ Preserve all option names.
- ✅ Maintain all existing hooks.
- ✅ Test thoroughly at each phase.
- ✅ Provide clear migration paths.
- ✅ Remove old code only when React versions are proven stable.

## 15. Coding Standards, Technology Constraints & Invariants

### 15.1 PHP – WordPress Coding Standards

- Follow existing patterns in BuddyBoss Platform:
  - Proper spacing, naming, and file organization.
  - Full docblocks, localization (`__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `esc_url()`).
  - Sanitize and escape all inputs/outputs:
    - `sanitize_text_field()`, `intval()`, `absint()`, etc.
- Ensure all new code passes PHPCS with WordPress rulesets (and any BuddyBoss-specific extensions).

### 15.2 React – WordPress React

- Use built-in WordPress packages:
  - `wp.element`
  - `wp.components`
  - `wp.i18n`
  - `wp.apiFetch`
  - `wp.data` (if using data stores)
- Build with your existing tooling:
  - Generate `*.asset.php` files.
  - Register/enqueue via `wp_register_script()` / `wp_enqueue_script()` with those dependencies.

### 15.3 Invariants From Existing Platform

- **Settings are already built with React** in many parts of the platform:
  - The new architecture **extends and reorganizes** the admin experience but does not switch frameworks.
- **Option/setting names remain exactly the same**:
  - All new React components (Dashboard, Settings grid, Activity/Groups settings, search) must read/write the **existing option keys**.
  - The Feature Registry and REST layer are thin abstractions over:
    - Existing `register_setting()` registrations.
    - Existing calls to `get_option()` / `update_option()`.
- **Component activation options unchanged**:
  - `bp-active-components` array remains the source of truth for component activation.
  - `bp_is_active( 'activity' )`, `bp_is_active( 'groups' )`, etc. continue to work unchanged.
  - Feature toggles update the same underlying options.
- **Database schema**:
  - **No database schema changes** required.
  - All data stored in existing WordPress options table.
  - No new tables, no migrations needed.
  - Existing options remain in same format.
- **Third-party compatibility**:
  - Any plugin reading or writing BuddyBoss options (or hooking into existing component APIs) continues to function.
  - New registry APIs simply offer a new, structured way for third parties to **add UI** inside the React admin.

## 16. File & Directory Structure

### 16.1 New PHP Files & Directories

**Feature Registry**:
```
src/bp-core/admin/features/
├── class-bb-feature-registry.php          # Main registry class
├── class-bb-feature-registry-compat.php   # Backward compatibility layer
├── class-bb-feature.php                   # Base feature class
├── class-bb-feature-activity.php          # Activity feature definition
├── class-bb-feature-groups.php            # Groups feature definition
└── ... (other feature classes)
```

**REST API Controllers**:
```
src/bp-core/admin/rest/
├── class-bb-rest-features-controller.php      # Features list/details
├── class-bb-rest-settings-search-controller.php  # Settings search
├── class-bb-rest-activity-controller.php     # Activity admin list
├── class-bb-rest-groups-controller.php        # Groups admin list
├── class-bb-rest-group-types-controller.php   # Group types
└── ... (other controllers)
```

**Admin Loader Updates**:
```
src/bp-core/admin/
├── bp-core-admin.php                        # Updated to load registry
└── features/                                 # New directory (see above)
```

### 16.2 New React Files & Directories

**React Admin Shell**:
```
src/bb-admin/                                # New directory for React admin
├── app/
│   ├── index.js                            # Main entry point
│   ├── App.js                              # Root component
│   ├── Router.js                           # Routing logic
│   └── components/
│       ├── Shell.js                        # Main shell layout
│       ├── Header.js                       # Top header with search
│       ├── DashboardScreen.js              # Dashboard component
│       └── SettingsGrid.js                 # Settings feature grid
├── features/
│   ├── activity/
│   │   ├── ActivitySettingsScreen.js       # Activity settings
│   │   └── ActivityListScreen.js          # All Activity list
│   ├── groups/
│   │   ├── GroupsSettingsScreen.js         # Groups settings
│   │   ├── GroupsListScreen.js             # All Groups list
│   │   ├── GroupEditScreen.js             # Edit Group form
│   │   ├── GroupCreateScreen.js            # Create Group form
│   │   ├── GroupTypesScreen.js            # Group Types management
│   │   └── GroupNavigationScreen.js        # Group Navigation designer
│   └── ... (other feature screens)
├── shared/
│   ├── components/
│   │   ├── FeatureCard.js                 # Feature card component
│   │   ├── SettingsField.js               # Generic field renderer
│   │   ├── SettingsSection.js             # Section component
│   │   └── SideNavigation.js              # Left navigation
│   └── utils/
│       ├── api.js                          # API helper functions
│       └── url-mapping.js                  # Legacy URL mapping
└── build/                                   # Built files (generated)
    ├── admin-shell.js
    ├── admin-shell.css
    ├── admin-shell.asset.php
    └── ... (feature bundles)
```

**Build Configuration**:
```
webpack.config.js                           # Webpack config (update existing)
package.json                                # Dependencies (update existing)
```

### 16.3 Files to Modify

**Core Admin Files**:
- `src/bp-core/classes/class-bp-admin.php` - Update menu callbacks
- `src/bp-core/bp-core-admin.php` - Load Feature Registry
- `src/bp-core/admin/settings/*.php` - Reference for option names (eventually deprecated)

**Component Files** (for reference, not modified initially):
- `src/bp-activity/bp-activity-admin.php` - Reference for list table logic
- `src/bp-groups/bp-groups-admin.php` - Reference for list table and edit logic

### 16.4 Files to Deprecate/Remove (Phase 6)

**After React versions are stable**:
- `src/bp-activity/bp-activity-admin.php` (list table UI only)
- `src/bp-groups/bp-groups-admin.php` (list table + metabox UI only)
- Old settings page templates (after all features migrated)

**Keep intact**:
- All low-level PHP logic (CRUD functions)
- All public APIs and hooks
- All option/meta storage mechanisms

### 16.5 Asset File Structure

**Generated Asset Files** (via build process):
```
src/bb-admin/build/
├── admin-shell.asset.php                   # Core shell dependencies
├── admin-shell.js                         # Core shell bundle
├── admin-shell.css                        # Core shell styles
├── feature-activity.asset.php             # Activity feature dependencies
├── feature-activity.js                    # Activity feature bundle
├── feature-activity.css                   # Activity feature styles
└── ... (other feature bundles)
```

**Asset File Format**:
```php
<?php
// admin-shell.asset.php
return array(
    'dependencies' => array(
        'wp-element',
        'wp-components',
        'wp-api-fetch',
        'wp-i18n',
        'wp-data',
    ),
    'version' => '1.0.0',
);
```

## 17. Additional Implementation Considerations

### 17.1 Error Handling & User Feedback

**Error States**:
- **Network errors**: Display user-friendly messages when REST API calls fail
- **Validation errors**: Show field-level and form-level validation messages
- **Save errors**: Display success/error notifications when settings fail to save
- **Loading states**: Show loading indicators during async operations

**Implementation**:
- Use WordPress `wp.data` store or React Context for error state management
- Display errors using WordPress `Notice` component or custom error UI
- Log errors to browser console in development mode
- Provide retry mechanisms for failed operations

**Example**:
```javascript
// Error handling in React components
try {
    const response = await wp.apiFetch({
        path: '/buddyboss/v1/features/activity/settings',
        method: 'POST',
        data: settings
    });
    // Show success notice
} catch (error) {
    // Show error notice with user-friendly message
    setError( error.message || __( 'Failed to save settings. Please try again.', 'buddyboss' ) );
}
```

### 17.2 Performance Optimization

**Bundle Size Management**:
- Core shell bundle should be < 200KB gzipped
- Per-feature bundles should be < 100KB gzipped each
- Use code splitting aggressively (route-based and feature-based)
- Tree-shake unused WordPress React components

**Lazy Loading Strategy**:
- Load feature bundles only when route is accessed AND feature is active
- Preload critical routes (Dashboard, Settings grid) on initial load
- Use dynamic `import()` for all feature-specific code
- Implement route-based code splitting

**Caching Strategy**:
- Cache REST API responses in browser (with appropriate cache headers)
- Cache feature registry data (changes infrequently)
- Use WordPress transients for expensive queries
- Implement optimistic UI updates for better perceived performance

**Optimization Checklist**:
- [ ] Bundle sizes meet targets
- [ ] Code splitting implemented correctly
- [ ] REST API responses cached appropriately
- [ ] Images optimized and lazy-loaded
- [ ] No unnecessary re-renders (use React.memo, useMemo, useCallback)
- [ ] Debounce search and filter inputs

### 17.3 Accessibility (a11y)

**WCAG 2.1 AA Compliance**:
- All interactive elements must be keyboard accessible
- Proper ARIA labels and roles
- Focus management (especially in modals and dynamic content)
- Screen reader announcements for state changes
- Color contrast ratios meet WCAG standards

**Implementation Requirements**:
- Use semantic HTML elements
- Add `aria-label`, `aria-describedby` where needed
- Implement focus traps in modals
- Announce loading states to screen readers
- Ensure all form fields have associated labels
- Test with screen readers (NVDA, JAWS, VoiceOver)

**WordPress React Components**:
- WordPress React components (`wp.components`) are generally accessible
- Ensure custom components follow WordPress accessibility patterns
- Use `wp.a11y` utilities for announcements

### 17.4 Internationalization (i18n)

**Text Domain**:
- Use `'buddyboss'` text domain for all Platform strings
- Add-on plugins use their own text domains
- All user-facing strings must be translatable

**Implementation**:
- Use `wp.i18n` in React: `const { __, _x, _n, sprintf } = wp.i18n;`
- Use WordPress PHP i18n functions: `__()`, `_e()`, `esc_html__()`, etc.
- Provide context for ambiguous strings using `_x()`
- Handle plural forms with `_n()`
- Use `sprintf()` for formatted strings

**RTL Support**:
- CSS should use logical properties (margin-inline-start vs margin-left)
- Test UI in RTL mode
- WordPress React components handle RTL automatically

**Example**:
```javascript
// React component
const { __ } = wp.i18n;
return <h2>{ __( 'Activity Settings', 'buddyboss' ) }</h2>;
```

```php
// PHP
echo esc_html__( 'Activity Settings', 'buddyboss' );
```

### 17.5 Build Process & Tooling

**Build Configuration**:
- Use existing BuddyBoss build tooling (webpack, Grunt, etc.)
- Generate `*.asset.php` files for dependency management
- Support both development and production builds
- Source maps for debugging in development

**Bundle Size Constraints**:
- **Shell Bundle**: Maximum 500KB (gzipped: ~150KB)
- **Per-Feature Bundles**: Maximum 200KB each (gzipped: ~60KB)
- **Total Admin Bundle**: Maximum 2MB (gzipped: ~600KB)
- **Implementation**:
```javascript
// webpack.config.js
module.exports = {
    performance: {
        maxEntrypointSize: 500000, // 500KB for shell
        maxAssetSize: 200000,      // 200KB per feature
        hints: 'error',            // Fail build if exceeded
    },
    optimization: {
        splitChunks: {
            chunks: 'all',
            maxSize: 200000,        // Split chunks larger than 200KB
        },
    },
};

// CI/CD check
const shellSize = fs.statSync('build/admin-shell.js').size;
const maxSize = 500 * 1024; // 500KB

if (shellSize > maxSize) {
    console.error(`ERROR: Shell bundle exceeds size limit: ${shellSize} bytes`);
    process.exit(1);
}
```

**Bundle Size Monitoring**:
- Track bundle sizes in CI/CD
- Alert if bundles exceed thresholds
- Use webpack-bundle-analyzer for optimization
- Set performance budgets in webpack config

**Asset Generation**:
- Each bundle should have corresponding `.asset.php` file:
  ```php
  <?php
  return array(
      'dependencies' => array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ),
      'version'      => '1.0.0',
  );
  ```
- Enqueue scripts using asset files:
  ```php
  $asset = require plugin_dir_path( __FILE__ ) . 'build/admin-shell.asset.php';
  wp_enqueue_script( 'bb-admin-shell', $url, $asset['dependencies'], $asset['version'], true );
  ```

**Asset Enqueuing Strategy**:
- **Main shell bundle** (`bb-admin-shell.js`):
  - Enqueued on all BuddyBoss admin pages (Dashboard, Settings, Components, etc.)
  - Contains: routing, layout, header, Settings grid, Dashboard
  - Dependencies: WordPress React packages
- **Per-feature bundles** (lazy-loaded):
  - `bb-admin-feature-activity.js` – Activity settings, All Activity list
  - `bb-admin-feature-groups.js` – Groups settings, All Groups, Create/Edit Group
  - Only loaded when:
    - Route matches feature (e.g., `/settings/activity`, `/groups/all`)
    - Feature is active (`is_active_callback` returns `true`)
  - Loaded via dynamic `import()` in React:
```javascript
// In React router
const ActivitySettings = lazy(() => 
    import(/* webpackChunkName: "feature-activity" */ './features/activity/SettingsScreen')
);
```
- **Conditional loading**:
  - Check feature active status before loading bundle
  - If feature disabled, show "Feature Disabled" message instead
  - Prevents loading unnecessary code

**Development Workflow**:
- Watch mode for automatic rebuilds during development
- Hot module replacement (HMR) if possible
- Linting (ESLint) and formatting (Prettier) checks
- Type checking if using TypeScript

### 17.6 State Management

**Approach**:
- Use WordPress `wp.data` for global state (if needed)
- Use React Context API for feature-specific state
- Use local component state for UI-only state
- Avoid Redux unless absolutely necessary (adds complexity)

**State Organization**:
- **Global state** (wp.data): Feature registry data, user preferences
- **Feature state** (Context): Current feature settings, form state
- **Component state** (useState): UI state (modals, dropdowns, etc.)

**Example Structure**:
```javascript
// Global store (wp.data)
const featuresStore = wp.data.select( 'buddyboss/features' );

// Feature context
const FeatureSettingsContext = React.createContext();

// Component state
const [isModalOpen, setIsModalOpen] = useState( false );
```

### 17.7 Security Considerations

**REST API Security**:
- All endpoints must check `manage_options` capability
- Verify nonces for state-changing operations
- Sanitize all inputs server-side
- Validate data types and ranges
- Rate limiting for API endpoints (prevent abuse)

**XSS Prevention**:
- Escape all output in PHP
- Use React's built-in XSS protection (auto-escaping)
- Sanitize user input before storing
- Validate file uploads (if any)

**CSRF Protection**:
- Use WordPress nonces for all AJAX/REST requests
- Verify nonces server-side
- Include nonce in REST API requests

**Implementation**:
```php
// REST endpoint
register_rest_route( 'buddyboss/v1', '/features/(?P<id>[a-zA-Z0-9-]+)/settings', array(
    'methods'  => 'POST',
    'callback' => function( $request ) {
        // Check capability
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', __( 'Insufficient permissions.', 'buddyboss' ), array( 'status' => 403 ) );
        }
        
        // Verify nonce
        if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
            return new WP_Error( 'invalid_nonce', __( 'Invalid security token.', 'buddyboss' ), array( 'status' => 403 ) );
        }
        
        // Sanitize and validate data
        $settings = $request->get_json_params();
        // ... sanitize and save
    },
    'permission_callback' => '__return_true', // Checked in callback
) );
```

### 17.8 Testing Strategy Details

**Unit Tests**:
- PHP: Test Feature Registry registration and REST endpoints
- React: Test individual components with Jest/React Testing Library
- Test utility functions and helpers

**Integration Tests**:
- Test REST API endpoints end-to-end
- Test React components with API integration
- Test feature activation/deactivation flow

**E2E Tests**:
- Test complete user workflows (navigate, edit settings, save)
- Test with different user roles
- Test with different license tiers (Free, Pro, Plus)
- Test backward compatibility scenarios

**Browser Testing**:
- Test in Chrome, Firefox, Safari, Edge
- Test on different screen sizes (responsive)
- Test with screen readers
- Test keyboard navigation

**Performance Tests**:
- Measure bundle load times
- Measure API response times
- Test with large datasets (many features, many settings)
- Monitor memory usage

### 17.9 Deployment & Release Strategy

**Staged Rollout**:
- **Phase 1**: Internal testing and QA
- **Phase 2**: Beta release to select users
- **Phase 3**: Gradual rollout (percentage of users)
- **Phase 4**: Full release

**Feature Flags**:
- Use `BB_USE_NEW_ADMIN` constant/filter for gradual rollout
- Allow per-site opt-in/opt-out
- Monitor error rates and user feedback
- **Feature Flag UI** (Admin UI to toggle):
  - Add admin setting to toggle `BB_USE_NEW_ADMIN` without code changes
  - Location: `Settings → BuddyBoss → Advanced → Use New Admin Interface`
  - Implementation:
```php
// Register setting
register_setting( 'bb_admin_settings', 'bb_use_new_admin', array(
    'type' => 'boolean',
    'default' => false,
) );

// Add UI in React admin (or legacy settings page)
add_settings_field(
    'bb_use_new_admin',
    __( 'Use New Admin Interface', 'buddyboss' ),
    'bb_render_new_admin_toggle',
    'bb_admin_settings'
);

function bb_render_new_admin_toggle() {
    $value = get_option( 'bb_use_new_admin', false );
    ?>
    <label>
        <input type="checkbox" name="bb_use_new_admin" value="1" <?php checked( $value ); ?>>
        <?php _e( 'Enable the new React-based admin interface', 'buddyboss' ); ?>
    </label>
    <p class="description">
        <?php _e( 'This will replace the current admin interface with the new React-based version.', 'buddyboss' ); ?>
    </p>
    <?php
}

// Apply filter
add_filter( 'bb_use_new_admin', function() {
    return (bool) get_option( 'bb_use_new_admin', false );
} );
```

**Beta Testing Acceptance Criteria**:
- **Success Metrics**:
  - Zero critical bugs (data loss, security issues)
  - Error rate < 0.1% (measured via error tracking)
  - User satisfaction score > 4.0/5.0 (from beta feedback)
  - Performance: Page load time < 2 seconds on average
  - Compatibility: 100% of tested third-party plugins work correctly
  - Accessibility: WCAG 2.1 AA compliance verified
- **Exit Criteria Checklist**:
  - [ ] All critical bugs resolved
  - [ ] All high-priority bugs resolved
  - [ ] Performance benchmarks met
  - [ ] Accessibility audit passed
  - [ ] Security audit passed
  - [ ] Documentation complete
  - [ ] Migration guide published
  - [ ] Support team trained
  - [ ] Rollback plan tested and documented
  - [ ] Beta user feedback incorporated

**Rollback Plan**:
- Keep old admin code until Phase 6 of migration
- Feature flag allows instant rollback
- Database changes are reversible (no schema changes)

**Documentation**:
- Update user documentation before release
- Create migration guide for developers
- Document breaking changes (if any)
- Provide upgrade path documentation

### 17.10 Monitoring & Analytics

**Error Tracking**:
- Log JavaScript errors to console (development)
- Consider error tracking service (production)
- Monitor REST API error rates
- Log PHP errors to WordPress debug log
- Track error patterns (which endpoints fail, which features cause issues)

**Performance Monitoring**:
- Track bundle load times
- Monitor API response times
- Track user interactions (optional, with consent)
- Monitor memory usage during feature activation/deactivation
- Track cache hit/miss rates

**User Feedback**:
- Collect feedback during beta phase
- Monitor support tickets related to new admin
- Track feature usage analytics

**Error Logging Implementation**:
```php
// Log errors to WordPress debug log
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( sprintf(
        'BuddyBoss Admin: Feature toggle failed for %s. Error: %s',
        $feature_id,
        $error_message
    ) );
}
```

**Rate Limiting Implementation**:
- Use WordPress transients to track API request counts per user/IP
- Limit: 60 requests per minute per user
- Return `429 Too Many Requests` when limit exceeded
- Log rate limit violations for security monitoring

## 18. Summary of Key Deliverables

- **Feature Registry (PHP)**:
  - Public APIs for features, sections, fields.
  - Third-party extensibility entrypoints.

- **REST API**:
  - Endpoints for features listing, feature details, settings update, and settings search.

- **React Admin Shell**:
  - Dashboard (BuddyBoss default screen).
  - Settings feature list with filters and license awareness.
  - Feature detail pages with side navigation and generic field controls.

- **Activity Feature**:
  - Migrated settings (aligned with Figma).
  - All Activity list in React with REST backend.

- **Groups Feature**:
  - Migrated settings (aligned with Figma).
  - All Groups, Create/Edit Group, Group Types, and Group Navigation implemented in React.

- **Search**:
  - Global settings search across registered features and fields.

- **Migration/Cleanup Plan**:
  - Phased rollout.
  - Removal of old admin PHP UIs once React equivalents are robust.
  - Continued compliance with WordPress Coding Standards and WordPress React constraints.

