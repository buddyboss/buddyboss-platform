# BuddyBoss Backend 2.0 - Technical Documentation

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [File Structure](#file-structure)
4. [PHP Implementation](#php-implementation)
5. [JavaScript/React Implementation](#javascriptreact-implementation)
6. [AJAX API Reference](#ajax-api-reference)
7. [Feature Registration](#feature-registration)
8. [Adding New Features](#adding-new-features)
9. [Security](#security)
10. [Caching Strategy](#caching-strategy)

---

## Overview

Backend 2.0 is a modern admin interface for BuddyBoss Platform built with:
- **PHP**: WordPress AJAX handlers, Feature Registry, Settings management
- **React**: WordPress Gutenberg components (`@wordpress/element`, `@wordpress/components`)
- **SCSS**: Custom styling following BuddyBoss design system

### Key Principles

1. **AJAX over REST API**: All admin operations use WordPress Admin AJAX for better security
2. **Feature-based Architecture**: Settings organized by features (Activity, Groups, etc.)
3. **Code Compartmentalization**: Feature code only loads when the feature is enabled
4. **Caching**: In-memory caching reduces redundant network requests

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        React Frontend                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │   Features   │  │   Settings   │  │    Lists     │           │
│  │    Screen    │  │    Screen    │  │   Screens    │           │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘           │
│         │                 │                 │                   │
│         └─────────────────┼─────────────────┘                   │
│                           │                                     │
│                    ┌──────▼──────┐                              │
│                    │  ajax.js    │  (AJAX Utility)              │
│                    └──────┬──────┘                              │
└───────────────────────────┼─────────────────────────────────────┘
                            │
                     admin-ajax.php
                            │
┌───────────────────────────┼─────────────────────────────────────┐
│                    PHP Backend                                  │
│         ┌─────────────────┼─────────────────┐                   │
│         │                 │                 │                   │
│  ┌──────▼──────┐  ┌───────▼──────┐  ┌──────▼───────┐            │
│  │  Settings   │  │   Activity   │  │    Groups    │            │
│  │    AJAX     │  │     AJAX     │  │     AJAX     │            │
│  └──────┬──────┘  └───────┬──────┘  └──────┬───────┘            │
│         │                 │                 │                   │
│         └─────────────────┼─────────────────┘                   │
│                           │                                     │
│                    ┌──────▼──────┐                              │
│                    │   Feature   │                              │
│                    │   Registry  │                              │
│                    └─────────────┘                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## File Structure

```
bp-core/
├── admin/
│   ├── bb-admin-settings-2.0-init.php          # Main initialization
│   ├── bb-admin-settings-2.0-page.php          # Admin page callback
│   ├── bb-admin-settings-2.0-activity.php      # Activity feature registration
│   ├── bb-admin-settings-2.0-groups.php        # Groups feature registration
│   ├── bb-admin-settings-2.0-migration.php     # Settings migration utilities
│   │
│   ├── class-bb-admin-settings-ajax.php        # Core AJAX handlers
│   ├── class-bb-admin-activity-ajax.php        # Activity AJAX handlers
│   ├── class-bb-admin-groups-ajax.php          # Groups AJAX handlers
│   │
│   └── bb-settings/
│       └── settings-2.0/
│           └── build/                          # Compiled React app
│               ├── index.js
│               ├── index.asset.php
│               └── styles/
│                   └── admin.css
│
├── classes/
│   ├── class-bb-feature-registry.php           # Feature registration system
│   ├── class-bb-icon-registry.php              # Icon management
│   ├── class-bb-settings-history.php           # Settings audit log
│   ├── class-bb-rest-response.php              # Response utilities
│   ├── class-bb-rest-dashboard-controller.php  # Dashboard REST (still used)
│   └── class-bb-feature-autoloader.php         # Conditional code loading

js/admin/settings-2.0/
├── index.js                                    # React app entry point
├── utils/
│   ├── ajax.js                                 # AJAX utility functions
│   └── featureCache.js                         # In-memory caching
│
├── components/
│   ├── AdminHeader.js                          # Top header component
│   ├── SideNavigation.js                       # Sidebar navigation
│   ├── SettingsForm.js                         # Form field renderer
│   │
│   ├── screens/
│   │   ├── FeaturesScreen.js                   # Features list
│   │   ├── FeatureSettingsScreen.js            # Feature settings
│   │   ├── DashboardScreen.js                  # Dashboard
│   │   ├── ActivityListScreen.js               # Activity management
│   │   ├── GroupsListScreen.js                 # Groups management
│   │   ├── GroupTypeScreen.js                  # Group types
│   │   ├── GroupNavigationScreen.js            # Group navigation
│   │   └── GroupEditScreen.js                  # Group edit
│   │
│   └── modals/
│       ├── ActivityEditModal.js                # Activity edit popup
│       ├── GroupModal.js                       # Group create/edit
│       └── GroupTypeModal.js                   # Group type modal
│
└── styles/
    └── scss/
        └── admin.scss                          # Main stylesheet
```

---

## PHP Implementation

### Initialization Flow

```php
// bb-admin-settings-2.0-init.php

// 1. Hook into bp_loaded (priority 4, before feature registration)
add_action( 'bp_loaded', 'bb_admin_settings_2_0_init', 4 );

function bb_admin_settings_2_0_init() {
    // Load core classes
    require_once 'class-bb-feature-registry.php';
    require_once 'class-bb-icon-registry.php';
    require_once 'class-bb-settings-history.php';
    
    // Initialize singletons
    bb_feature_registry();
    bb_icon_registry();
    bb_settings_history();
    
    // Load AJAX handlers
    require_once 'class-bb-admin-settings-ajax.php';
    require_once 'class-bb-admin-activity-ajax.php';
    require_once 'class-bb-admin-groups-ajax.php';
    
    // Load feature registrations
    require_once 'bb-admin-settings-2.0-activity.php';
    require_once 'bb-admin-settings-2.0-groups.php';
}
```

### AJAX Handler Structure

All AJAX handlers follow this pattern:

```php
class BB_Admin_Settings_Ajax {

    const NONCE_ACTION = 'bb_admin_settings_2_0';

    public function __construct() {
        $this->register_ajax_handlers();
    }

    private function register_ajax_handlers() {
        add_action( 'wp_ajax_bb_admin_get_features', array( $this, 'get_features' ) );
        // ... more handlers
    }

    private function verify_request() {
        // Verify nonce
        if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }

        // Verify capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
        }

        return true;
    }

    public function get_features() {
        $this->verify_request();
        
        // Your logic here...
        
        wp_send_json_success( $data );
    }
}

// Initialize
new BB_Admin_Settings_Ajax();
```

### Feature Registry API

```php
// Register a feature
bb_register_feature( 'activity', array(
    'label'        => __( 'Activity', 'buddyboss' ),
    'description'  => __( 'Allow members to post updates...', 'buddyboss' ),
    'category'     => 'community',
    'icon'         => array( 'type' => 'dashicon', 'slug' => 'dashicons-update' ),
    'license_tier' => 'free',
) );

// Register a side panel (appears in sidebar)
bb_register_side_panel( 'activity', 'activity_settings', array(
    'title'      => __( 'Activity Settings', 'buddyboss' ),
    'icon'       => array( 'type' => 'dashicon', 'slug' => 'dashicons-admin-settings' ),
    'order'      => 10,
    'is_default' => true,
) );

// Register a section (card in main content)
bb_register_feature_section( 'activity', 'activity_settings', 'main', array(
    'title'       => __( 'Activity Settings', 'buddyboss' ),
    'description' => '',
    'order'       => 10,
) );

// Register a field
bb_register_feature_field( 'activity', 'activity_settings', 'main', array(
    'name'        => '_bp_enable_activity_edit',
    'label'       => __( 'Edit Activity', 'buddyboss' ),
    'type'        => 'toggle',
    'description' => __( 'Allow members to edit their posts.', 'buddyboss' ),
    'default'     => '1',
    'order'       => 10,
) );

// Register navigation item (e.g., "All Activities" link)
bb_register_navigation_item( 'activity', 'all_activity', array(
    'label' => __( 'All Activities', 'buddyboss' ),
    'route' => '/activity/all',
    'icon'  => 'dashicons-list-view',
    'order' => 100,
) );
```

### Field Types

| Type | Description | Options |
|------|-------------|---------|
| `toggle` | On/Off switch | `invert_value` for inverted option names |
| `select` | Dropdown | `options` array with `label`/`value` |
| `radio` | Radio buttons | `options` array |
| `checkbox_list` | Multiple checkboxes | `options` array |
| `text` | Text input | `prefix`, `suffix` |
| `number` | Number input | `min`, `max`, `prefix`, `suffix` |
| `textarea` | Multi-line text | |
| `image_radio` | Visual image selection | `options` with `image` URLs |
| `toggle_list` | Multiple toggles (separate options) | `options` array |
| `toggle_list_array` | Multiple toggles (single array option) | `options` array |
| `media` | Media library picker | |

### Conditional Fields

Fields can be conditionally shown based on parent field values:

```php
bb_register_feature_field( 'activity', 'activity_settings', 'main', array(
    'name'         => '_bb_activity_edit_time',
    'label'        => __( 'Edit time limit', 'buddyboss' ),
    'type'         => 'number',
    'parent_field' => '_bp_enable_activity_edit',  // Only show when this is enabled
    'suffix'       => 'Minutes',
    'order'        => 15,
) );
```

---

## JavaScript/React Implementation

### AJAX Utility

All AJAX calls go through `utils/ajax.js`:

```javascript
// utils/ajax.js

export function ajaxFetch(action, data = {}) {
    const ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';
    const nonce = window.bbAdminData?.ajaxNonce || '';

    const formData = new FormData();
    formData.append('action', action);
    formData.append('nonce', nonce);

    Object.keys(data).forEach((key) => {
        formData.append(key, data[key]);
    });

    return fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
    }).then((response) => response.json());
}

// Exported functions
export function getFeatures() {
    return ajaxFetch('bb_admin_get_features');
}

export function activateFeature(featureId) {
    return ajaxFetch('bb_admin_activate_feature', { feature_id: featureId });
}

// ... more exports
```

### Component Structure

```javascript
// screens/FeatureSettingsScreen.js

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { ajaxFetch } from '../../utils/ajax';
import { getCachedFeatureData, setCachedFeatureData } from '../../utils/featureCache';

export default function FeatureSettingsScreen({ featureId, onNavigate }) {
    const [settings, setSettings] = useState({});
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        loadSettings();
    }, [featureId]);

    const loadSettings = async () => {
        // Check cache first
        const cached = getCachedFeatureData(featureId);
        if (cached) {
            setSettings(cached);
            setIsLoading(false);
            return;
        }

        // Fetch from server
        const response = await ajaxFetch('bb_admin_get_feature_settings', { 
            feature_id: featureId 
        });
        
        if (response.success) {
            setSettings(response.data);
            setCachedFeatureData(featureId, response.data);
        }
        setIsLoading(false);
    };

    const handleSave = async (changedSettings) => {
        setIsSaving(true);
        await ajaxFetch('bb_admin_save_feature_settings', {
            feature_id: featureId,
            settings: JSON.stringify(changedSettings),
        });
        setIsSaving(false);
    };

    if (isLoading) {
        return <Spinner />;
    }

    return (
        <div className="bb-admin-feature-settings">
            {/* Render settings form */}
        </div>
    );
}
```

### Routing

Hash-based routing in `index.js`:

```javascript
// index.js

const App = () => {
    const [route, setRoute] = useState(window.location.hash.slice(1) || '/');

    useEffect(() => {
        const handleHashChange = () => {
            setRoute(window.location.hash.slice(1) || '/');
        };
        window.addEventListener('hashchange', handleHashChange);
        return () => window.removeEventListener('hashchange', handleHashChange);
    }, []);

    const navigate = (path) => {
        window.location.hash = path;
    };

    // Route matching
    if (route === '/' || route === '/features') {
        return <FeaturesScreen onNavigate={navigate} />;
    }
    
    if (route.startsWith('/settings/')) {
        const featureId = route.split('/')[2];
        return <FeatureSettingsScreen featureId={featureId} onNavigate={navigate} />;
    }
    
    // ... more routes
};
```

---

## AJAX API Reference

### Core Settings

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `bb_admin_get_features` | GET | - | Get all features |
| `bb_admin_activate_feature` | POST | `feature_id` | Activate a feature |
| `bb_admin_deactivate_feature` | POST | `feature_id` | Deactivate a feature |
| `bb_admin_get_feature_settings` | GET | `feature_id` | Get feature settings |
| `bb_admin_save_feature_settings` | POST | `feature_id`, `settings` | Save feature settings |
| `bb_admin_search_settings` | GET | `query` | Search settings |

### Platform Settings

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `bb_admin_get_platform_settings` | GET | `options` (comma-separated) | Get specific options |
| `bb_admin_save_platform_setting` | POST | `option_name`, `option_value` | Save single option |
| `bb_admin_get_appearance_settings` | GET | - | Get appearance settings |
| `bb_admin_save_appearance_settings` | POST | `group_nav_display`, etc. | Save appearance |

### Activity

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `bb_admin_get_activity_types` | GET | - | Get activity types |
| `bb_admin_get_activity_topics` | GET | - | Get activity topics |
| `bb_admin_get_activities` | GET | `page`, `per_page`, etc. | List activities |
| `bb_admin_get_activity` | GET | `activity_id` | Get single activity |
| `bb_admin_update_activity` | POST | `activity_id`, fields... | Update activity |
| `bb_admin_delete_activity` | POST | `activity_id` | Delete activity |
| `bb_admin_spam_activity` | POST | `activity_id`, `is_spam` | Mark spam/ham |

### Groups

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `bb_admin_get_groups` | GET | `page`, `per_page`, etc. | List groups |
| `bb_admin_get_group` | GET | `group_id` | Get single group |
| `bb_admin_create_group` | POST | `name`, `description`, etc. | Create group |
| `bb_admin_update_group` | POST | `group_id`, fields... | Update group |
| `bb_admin_delete_group` | POST | `group_id` | Delete group |
| `bb_admin_get_group_types` | GET | - | List group types |
| `bb_admin_create_group_type` | POST | `name`, `label`, etc. | Create type |
| `bb_admin_update_group_type` | POST | `type_id`, fields... | Update type |
| `bb_admin_delete_group_type` | POST | `type_id` | Delete type |

---

## Adding New Features

### Step 1: Register Feature (PHP)

Create `bb-admin-settings-2.0-{feature}.php`:

```php
<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bb_features_loaded', 'bb_register_my_feature_settings' );

function bb_register_my_feature_settings() {
    // 1. Register the feature
    bb_register_feature( 'my_feature', array(
        'label'        => __( 'My Feature', 'buddyboss' ),
        'description'  => __( 'Description here...', 'buddyboss' ),
        'category'     => 'community',
        'icon'         => array( 'type' => 'dashicon', 'slug' => 'dashicons-star-filled' ),
        'license_tier' => 'free',
    ) );

    // 2. Register side panel
    bb_register_side_panel( 'my_feature', 'main_settings', array(
        'title'      => __( 'Settings', 'buddyboss' ),
        'icon'       => array( 'type' => 'dashicon', 'slug' => 'dashicons-admin-settings' ),
        'order'      => 10,
        'is_default' => true,
    ) );

    // 3. Register section
    bb_register_feature_section( 'my_feature', 'main_settings', 'general', array(
        'title' => __( 'General Settings', 'buddyboss' ),
        'order' => 10,
    ) );

    // 4. Register fields
    bb_register_feature_field( 'my_feature', 'main_settings', 'general', array(
        'name'        => 'my_feature_enabled',
        'label'       => __( 'Enable Feature', 'buddyboss' ),
        'type'        => 'toggle',
        'default'     => '1',
        'order'       => 10,
    ) );
}
```

### Step 2: Load Feature File

In `bb-admin-settings-2.0-init.php`:

```php
if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-my-feature.php' ) ) {
    require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-my-feature.php';
}
```

### Step 3: Add AJAX Handlers (if needed)

Create `class-bb-admin-my-feature-ajax.php`:

```php
<?php
class BB_Admin_My_Feature_Ajax {

    const NONCE_ACTION = 'bb_admin_settings_2_0';

    public function __construct() {
        add_action( 'wp_ajax_bb_admin_get_my_items', array( $this, 'get_items' ) );
    }

    private function verify_request() {
        if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
        }
        return true;
    }

    public function get_items() {
        $this->verify_request();
        // Your logic...
        wp_send_json_success( $data );
    }
}

new BB_Admin_My_Feature_Ajax();
```

### Step 4: Add React Screen (if needed)

Create `components/screens/MyFeatureScreen.js`:

```javascript
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ajaxFetch } from '../../utils/ajax';

export default function MyFeatureScreen({ onNavigate }) {
    const [items, setItems] = useState([]);

    useEffect(() => {
        loadItems();
    }, []);

    const loadItems = async () => {
        const response = await ajaxFetch('bb_admin_get_my_items');
        if (response.success) {
            setItems(response.data);
        }
    };

    return (
        <div className="bb-admin-my-feature">
            {/* Your UI */}
        </div>
    );
}
```

### Step 5: Add Route

In `index.js`:

```javascript
import MyFeatureScreen from './components/screens/MyFeatureScreen';

// In router:
if (route.startsWith('/my-feature')) {
    return <MyFeatureScreen onNavigate={navigate} />;
}
```

---

## Security

### Nonce Verification

All AJAX requests require nonce verification:

```php
// PHP - Verify
check_ajax_referer( 'bb_admin_settings_2_0', 'nonce', false );

// JavaScript - Send
const nonce = window.bbAdminData?.ajaxNonce;
formData.append('nonce', nonce);
```

### Capability Checks

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
}
```

### Input Sanitization

```php
$option_name = sanitize_text_field( wp_unslash( $_POST['option_name'] ) );
$description = wp_kses_post( wp_unslash( $_POST['description'] ) );
$ids = array_map( 'absint', (array) $_POST['ids'] );
```

### Whitelisting

Platform settings use whitelists:

```php
$allowed_options = array(
    'bp-disable-group-type-creation',
    'bp-enable-group-auto-join',
);

if ( ! in_array( $option_name, $allowed_options, true ) ) {
    wp_send_json_error( array( 'message' => 'Option not allowed.' ), 403 );
}
```

---

## Caching Strategy

### In-Memory Cache (JavaScript)

```javascript
// utils/featureCache.js

const featureCache = {};

export function getCachedFeatureData(featureId) {
    return featureCache[featureId] || null;
}

export function setCachedFeatureData(featureId, data) {
    featureCache[featureId] = data;
}

export function getCachedSidebarData(featureId) {
    const cached = featureCache[featureId];
    if (cached) {
        return {
            sidePanels: cached.side_panels || [],
            navItems: cached.navigation || [],
        };
    }
    return null;
}

export function invalidateFeatureCache(featureId) {
    if (featureId) {
        delete featureCache[featureId];
    } else {
        Object.keys(featureCache).forEach(key => delete featureCache[key]);
    }
}
```

### Usage

```javascript
// Check cache before fetching
const cached = getCachedSidebarData('activity');
if (cached) {
    setSidePanels(cached.sidePanels);
    return;
}

// Fetch and cache
const response = await ajaxFetch('bb_admin_get_feature_settings', { feature_id: 'activity' });
if (response.success) {
    setCachedFeatureData('activity', response.data);
}

// Invalidate after save
await saveSettings();
invalidateFeatureCache('activity');
```

---

## Build Process

### Development

```bash
cd wp-content/plugins/buddyboss-platform
npm run build:admin:settings-2.0
```

### Watch Mode (for development)

```bash
npm run start:admin:settings-2.0
```

### Build Output

- `src/bp-core/admin/bb-settings/settings-2.0/build/index.js` - Main JS bundle
- `src/bp-core/admin/bb-settings/settings-2.0/build/styles/admin.css` - Compiled CSS

---

## Troubleshooting

### Common Issues

1. **AJAX returns "Security check failed"**
   - Ensure nonce is being sent
   - Check `window.bbAdminData.ajaxNonce` exists
   - Verify nonce action matches (`bb_admin_settings_2_0`)

2. **Settings not saving**
   - Check field `name` matches WordPress option name
   - Verify `sanitize_callback` if custom
   - Check browser console for errors

3. **Feature not appearing**
   - Ensure feature registration file is loaded
   - Check `bb_features_loaded` hook priority
   - Verify `bp_is_active()` for component

4. **React component not rendering**
   - Check route matching in `index.js`
   - Verify component imports
   - Check browser console for React errors

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 3.0.0 | 2024 | Initial Backend 2.0 release |

---

*Last updated: January 2026*
