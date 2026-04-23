# BuddyBoss Platform Features

This directory contains all BuddyBoss Platform features organized by category.

## Directory Structure

```
features/
├── integrations/          # Third-party plugin integrations
│   ├── learndash/        # LearnDash LMS integration
│   ├── pusher/           # Pusher real-time notifications
│   ├── recaptcha/        # Google reCAPTCHA anti-spam
│   ├── buddyboss-app/    # BuddyBoss App integration
│   └── compatibility/    # WordPress compatibility layer
│
└── community/            # Core community features
    └── reactions/        # Like & Reactions system
```

## Feature Structure Standard

Each feature follows a self-contained structure:

```
{feature-name}/
├── feature-config.php         # Feature registration (REQUIRED)
├── loader.php                 # Feature loader (REQUIRED)
├── classes/                   # PHP classes
│   ├── class-{feature}-main.php
│   └── ...
├── admin/                     # Admin functionality
│   ├── settings.php
│   └── ...
├── includes/                  # Functions, filters, actions
│   ├── functions.php
│   ├── filters.php
│   └── actions.php
├── assets/                    # Frontend assets
│   ├── css/
│   ├── js/
│   └── images/
└── templates/                 # Template files
```

## Feature Registration (feature-config.php)

Each feature must have a `feature-config.php` file that registers it with the Feature Registry.

### Integration Example:
```php
<?php
defined( 'ABSPATH' ) || exit;

bb_register_integration(
    'learndash',
    array(
        'label'                 => __( 'LearnDash', 'buddyboss' ),
        'description'           => __( 'LMS integration', 'buddyboss' ),
        'required_plugin_const' => 'LEARNDASH_VERSION',
        'php_loader'            => function() {
            require_once __DIR__ . '/loader.php';
        },
        'order'                 => 210,
    )
);
```

### Community Feature Example:
```php
<?php
defined( 'ABSPATH' ) || exit;

bb_register_feature(
    'reactions',
    array(
        'label'         => __( 'Like & Reactions', 'buddyboss' ),
        'description'   => __( 'Allow members to like content', 'buddyboss' ),
        'category'      => 'community',
        'license_tier'  => 'free',
        'standalone'    => true,
        'php_loader'    => function() {
            require_once __DIR__ . '/loader.php';
        },
        'order'         => 55,
    )
);
```

## Feature Loader (loader.php)

The loader file is responsible for loading all feature files when the feature is active.

```php
<?php
defined( 'ABSPATH' ) || exit;

// Load classes
require_once __DIR__ . '/classes/class-feature-main.php';

// Load includes
require_once __DIR__ . '/includes/functions.php';

// Initialize feature
add_action( 'bp_init', function() {
    Feature_Main::instance();
});
```

## Auto-Discovery

Features are automatically discovered and loaded by the Feature Autoloader:
1. Scans `features/integrations/` and `features/community/`
2. Looks for `feature-config.php` in each subdirectory
3. Loads feature registration during `bp_core_loaded` hook
4. Features only load their code when active

## Migration from Old Structure

### Integrations
Old location: `src/bp-integrations/{integration-name}/`
New location: `src/features/integrations/{integration-name}/`

### Core Features
Old location: Scattered in `src/bp-core/`
New location: `src/features/community/{feature-name}/`

## Best Practices

1. **Self-Contained**: Each feature should be completely independent
2. **No Cross-Dependencies**: Features should not depend on other features' internal code
3. **Use Hooks**: Communicate between features using WordPress actions/filters
4. **Namespace**: Use unique function prefixes to avoid conflicts
5. **Autoload**: Use Composer autoloading for classes when possible

## Adding a New Feature

1. Create feature folder: `features/{category}/{feature-name}/`
2. Create `feature-config.php` with registration
3. Create `loader.php` with loading logic
4. Create feature classes and files
5. Test feature enable/disable functionality
6. Verify no conflicts with other features

---

Last Updated: January 2026
BuddyBoss Platform Version: 3.0.0
