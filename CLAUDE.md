# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

BuddyBoss Platform is a WordPress plugin that combines **BuddyPress** (social networking) and **bbPress** (forums) into a unified community platform. It adds member profiles, activity streams, groups, messaging, notifications, forums, media, and more. It's a fork of both BuddyPress and bbPress with significant architectural enhancements and additional features.

**Current version:** 2.19.0 (Platform), 3.1.0 (npm package), BP compatibility version 4.3.0

**Key integration:** The `bp-forums/` component provides deep integration with bbPress, handling discussion forums, topics, and replies within the BuddyBoss ecosystem.

## Development Commands

### Building Assets

```bash
# Install dependencies
npm install

# Build all admin interfaces
npm run build:admin

# Build specific admin targets
npm run build:admin:readylaunch
npm run build:admin:rl-onboarding

# Build blocks
npm run build:blocks

# Watch mode for development (rebuilds on file changes)
npm run watch:admin:readylaunch
npm run watch:admin:rl-onboarding
npm run watch:readylaunch-header
```

### Running Tests

```bash
# PHP unit tests
composer test

# LearnDash integration tests
composer test-ld

# Run specific test file
vendor/bin/phpunit tests/phpunit/testcases/path/to/test.php
```

### Linting & Code Quality

```bash
# PHP CodeSniffer
composer lint-php

# Auto-fix PHP code style issues
composer lint-php-fix

# Or use direct paths (for approved commands)
~/.composer/vendor/squizlabs/php_codesniffer/bin/phpcs
~/.composer/vendor/squizlabs/php_codesniffer/bin/phpcbf

# JavaScript validation
npm run lint-js

# CSS linting
npm run lint-css

# Lint everything
composer lint
```

**PHP standards:** WordPress Coding Standards + PHPCompatibilityWP (minimum PHP 5.6)
**JavaScript standards:** WordPress JavaScript Coding Standards

### Grunt Tasks

```bash
# Build production release
grunt build

# Build CSS from SASS
grunt sass

# Generate RTL CSS
grunt rtlcss

# Minify assets
grunt uglify
grunt cssmin

# Watch for changes
grunt watch
```

## Architecture Overview

### Component-Based Structure (Legacy)

The plugin is organized into **components** under `src/`:

- **`bp-core/`** - Core framework, admin infrastructure, hooks, template loader
- **`bp-activity/`** - Activity streams, posts, comments
- **`bp-groups/`** - Groups, group types, group hierarchies
- **`bp-members/`** - Member profiles and directories
- **`bp-messages/`** - Private messaging
- **`bp-notifications/`** - Notification system
- **`bp-friends/`** - Friendship connections
- **`bp-media/`** - Photo uploads and galleries
- **`bp-video/`** - Video uploads and playback
- **`bp-document/`** - Document management
- **`bp-forums/`** - bbPress integration (discussion forums, topics, replies)
- **`bp-xprofile/`** - Extended profile fields
- **`bp-search/`** - Global search
- **`bp-moderation/`** - Content moderation
- **`bp-performance/`** - Performance optimizations
- **`bp-settings/`** - User settings
- **`bp-integrations/`** - Third-party integrations

Each component typically contains:
- `bp-{component}-loader.php` - Component initialization
- `bp-{component}-functions.php` - Public API functions
- `bp-{component}-template.php` - Template tags
- `bp-{component}-actions.php` - Action/filter hooks
- `classes/` - PHP classes
- `screens/` - Frontend screen handlers
- `admin/` - Admin interfaces

### Boot Sequence

1. **`bp-loader.php`** (root) - Defines constants, loads Composer autoload
2. **`src/bp-loader.php`** - Compatibility checks, textdomain setup
3. **`src/class-buddypress.php`** - Main singleton (`buddypress()`)
4. Components load via `bp-{component}-loader.php` files
5. Admin loads via `bp-core-admin.php` → `class-bp-admin.php`

### ReadyLaunch Frontend

**Location:** `src/bp-templates/bp-nouveau/readylaunch/`

ReadyLaunch is a modern UI mode that uses MediumEditor for rich text editing. Key aspects:

#### JavaScript Architecture
- **MediumEditor instances:** Created for activity posts, forum topics/replies, comments
- **Emoji handling:** Two-step process:
  1. Frontend renders emoji as `<img class="emojioneemoji" data-emoji-char="😁">`
  2. Before submit: Replace `<img>` tags with actual emoji characters from `data-emoji-char`

**Critical pattern:** When calling `editor.getContent()`, always convert emoji:
```javascript
var raw_content = editor.getContent();
var dummy_element = document.createElement('div');
dummy_element.innerHTML = raw_content;

// Transform emoji image into emoji unicode
jQuery(dummy_element).find('img.emojioneemoji').replaceWith(
    function() {
        return this.dataset.emojiChar;
    }
);

// Use jQuery(dummy_element).html() for the converted content
```

#### Template Structure
Templates require `.bb-rl-screen-content` wrapper for modal functionality:
```php
<div class="bb-rl-screen-content">
    <!-- Content here -->
</div>
```

### Template System

BuddyBoss Platform uses the **bp-nouveau** template pack (bp-legacy has been removed).

**Template Location:** `src/bp-templates/bp-nouveau/`

**Template Hierarchy:**
1. Theme templates (highest priority): `wp-content/themes/{theme}/buddypress/`
2. Child theme templates: `wp-content/themes/{child-theme}/buddypress/`
3. Plugin templates: `src/bp-templates/bp-nouveau/buddypress/`

**Template Modes:**
- **Standard Mode:** `buddypress/` directory
- **ReadyLaunch Mode:** `readylaunch/` directory (modern UI with MediumEditor)

**Template Override Pattern:**
```php
// Copy from plugin to theme
// From: wp-content/plugins/buddyboss-platform/src/bp-templates/bp-nouveau/buddypress/members/single/profile.php
// To:   wp-content/themes/your-theme/buddypress/members/single/profile.php
```

**Template Functions File:**
- `buddypress-functions.php` in theme root for template customizations
- Acts like `functions.php` but for BuddyPress templates

**Template Loading:**
- Uses `bp_locate_template()` function
- Checks theme first, then plugin templates
- Supports template parts with `bp_get_template_part()`

### Admin React Interface

**Source:** `src/js/admin/readylaunch/` and `src/js/admin/rl-onboarding/`
**Build output:** `src/bp-core/admin/bb-settings/{target}/build/`

- Built with **@wordpress/scripts** and custom webpack config
- Uses WordPress components (`@wordpress/components`, `@wordpress/element`)
- SCSS compiled separately via sass CLI
- Embla Carousel for UI interactions

**Build targets:**
- `readylaunch` - Quick setup wizard
- `rl-onboarding` - Onboarding experience

**Build process:**
1. JavaScript/JSX compiled via webpack (wp-scripts)
2. SCSS compiled separately to CSS via sass CLI
3. Output placed in `src/bp-core/admin/bb-settings/{target}/build/`

## File Organization Patterns

### Source vs Build

- **Source code:** `src/` directory (PHP, JavaScript, SCSS)
- **Build artifacts:** Compiled assets in component directories
- **JavaScript source:** `src/js/admin/{target}/` and `src/js/blocks/`
- **JavaScript build:** `src/bp-core/admin/bb-settings/{target}/build/`

### PHP Class Loading

- **Composer autoload:** `includes/` directory (scoped dependencies)
- **Manual requires:** Component classes loaded in `bp-{component}-loader.php`
- **Admin classes:** `src/bp-core/classes/` and `src/bp-core/admin/classes/`

### Testing Structure

- **Bootstrap:** `tests/bootstrap.php` (loads WordPress test suite)
- **Test cases:** `tests/phpunit/testcases/` organized by component
- **PHPUnit config:** `phpunit.xml.dist`

## Important Conventions

### WordPress Integration

- **Hooks:** Extensive use of WordPress actions/filters
- **i18n:** Text domain is `buddyboss`
- **Capabilities:** Custom capability checks via `bp_current_user_can()`
- **Database:** Uses `$wpdb` with custom BP tables (`bp_activity`, `bp_groups`, etc.)

### BuddyPress & bbPress Compatibility

- The plugin maintains BuddyPress function signatures for compatibility
- `$buddypress` global object is the main singleton
- Component activation checks use `bp_is_active('component')`
- bbPress integration is embedded in `bp-forums/` component
- Forum functions use `bbp_` prefix (e.g., `bbp_get_forum()`, `bbp_insert_topic()`)

### Multisite Considerations

BuddyBoss Platform fully supports WordPress Multisite networks.

**Multisite Detection:**
```php
// Check if running on multisite
if ( is_multisite() ) {
    // Multisite-specific code
}

// Check if in network admin
if ( bp_core_do_network_admin() ) {
    // Network admin context
    $url = network_admin_url( 'admin.php?page=bp-settings' );
} else {
    // Site admin context
    $url = admin_url( 'admin.php?page=bp-settings' );
}
```

**Multisite-Specific Functions:**
- `bp_core_do_network_admin()` - Check network admin context
- `bp_is_network_activated()` - Check if network-activated
- `network_admin_url()` - Get network admin URL
- `get_site_option()` / `update_site_option()` - Network-wide options

**Important Multisite Patterns:**
- Components can be network-activated or per-site activated
- Settings can be network-wide or per-site
- Users exist network-wide, but memberships are per-site
- Blogs component is multisite-specific
- Some features only available in network admin

**Multisite Testing:**
- Always test features on both single-site and multisite
- Test network admin vs site admin interfaces
- Verify option storage (site vs network options)
- Test cross-site functionality

### Coding Standards

#### PHP
- Follow **WordPress Coding Standards** (enforced via PHPCS with WordPress ruleset)
- All PHP code must pass `phpcs` validation with WordPress standard
- Run `composer lint-php` before committing to check for violations
- Run `composer lint-php-fix` to auto-fix common issues
- See `phpcs.xml` for specific rules and exclusions
- Excluded paths: `deprecated/`, `bp-integrations/`, `vendor/`, `node_modules/`
- Minimum PHP compatibility: 5.6 (checked via PHPCompatibilityWP)

**Key WordPress PHP Conventions:**
- Use tabs (not spaces) for indentation
- Yoda conditions for comparisons: `if ( 'value' === $variable )`
- Space after control structures: `if ( condition )`, `foreach ( $items as $item )`
- Braces on same line for control structures
- Single quotes for strings (unless variable interpolation needed)
- Proper escaping for output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize input: `sanitize_text_field()`, `absint()`, etc.
- Use WordPress functions over PHP equivalents when available

**Naming Conventions:**

BuddyBoss Platform uses specific prefixes for functions, classes, and files:

**Function Prefixes:**
- `bp_` - BuddyPress core functions (e.g., `bp_activity_has_directory()`, `bp_get_activity()`)
  - Use for: Core social networking features (activity, groups, members, messages, etc.)
- `bb_` - BuddyBoss-specific enhancements (e.g., `bb_get_user_name()`, `bb_recaptcha_display()`)
  - Use for: BuddyBoss Platform exclusive features, integrations, widgets, and enhancements
- `bbp_` - bbPress forum functions (e.g., `bbp_insert_topic()`, `bbp_get_forum()`)
  - Use for: Forum-specific functionality (topics, replies, forum management)

**Class Prefixes:**
- `BP_` - BuddyPress core classes (e.g., `BP_Activity_Component`, `BP_Admin`)
  - Use for: Core component classes, templates, and utilities
- `BB_` - BuddyBoss-specific classes (e.g., `BB_Post_Notification`, `BB_Background_Process`)
  - Use for: BuddyBoss Platform exclusive features and enhancements
- `BBP_` - bbPress classes (e.g., `BBP_Forum`, `BBP_Topic`)
  - Use for: Forum-related classes and data structures

**File Naming:**
- `class-bp-*.php` - BuddyPress core class files
- `class-bb-*.php` - BuddyBoss-specific class files
- `bp-{component}-functions.php` - Component public functions
- `bp-{component}-filters.php` - Component filters and actions
- `bp-{component}-loader.php` - Component initialization

**Constants:**
- Use `BP_` prefix (e.g., `BP_VERSION`, `BP_PLATFORM_VERSION`)
- All uppercase with underscores

**Hooks (Actions & Filters):**
- Use `bp_` prefix (e.g., `bp_loaded`, `bp_init`, `bp_activity_after_save`)
- Lowercase with underscores

**Example:**
```php
// Good - WordPress PHPCS standard with proper naming
function bb_get_user_display_name( $user_id ) {
	if ( empty( $user_id ) ) {
		return '';
	}

	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return '';
	}

	return esc_html( $user->display_name );
}

// Good - BuddyBoss class naming
class BB_User_Profile_Widget extends WP_Widget {
	// Class implementation
}

// Avoid - Not WordPress standard
function getUserName($user_id) {
    if (empty($user_id)) {
        return "";
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return "";
    }

    return $user->display_name; // Missing escaping, wrong naming
}
```

#### Translation & Internationalization (i18n)

**Text Domain:** `buddyboss`

All user-facing strings must be internationalized:

```php
// Simple string
__( 'Hello World', 'buddyboss' );

// Echo string
_e( 'Hello World', 'buddyboss' );

// String with HTML (escape after)
esc_html__( 'Hello World', 'buddyboss' );
esc_html_e( 'Hello World', 'buddyboss' );

// Plural forms
_n( '%s item', '%s items', $count, 'buddyboss' );
sprintf( _n( '%s item', '%s items', $count, 'buddyboss' ), number_format_i18n( $count ) );

// Context (when same string has different meanings)
_x( 'Post', 'noun', 'buddyboss' );
_x( 'Post', 'verb', 'buddyboss' );

// Escaping + translation combined
esc_html__( 'Settings', 'buddyboss' );
esc_attr__( 'Search', 'buddyboss' );
```

**Translation Functions:**
- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `_n()` - Plural forms
- `_x()` - String with context
- `esc_html__()` - Translate + escape HTML
- `esc_attr__()` - Translate + escape attribute
- `esc_html_e()` - Echo + translate + escape HTML

**JavaScript Translation:**
```php
// Localize script with translations (PHP side)
wp_localize_script(
    'bb-script',
    'bbTranslations',
    array(
        'confirm_delete' => __( 'Are you sure?', 'buddyboss' ),
        'loading'        => __( 'Loading...', 'buddyboss' ),
    )
);
```

```javascript
// Use in JavaScript
alert( bbTranslations.confirm_delete );
```

**Translation Best Practices:**
- Always use `buddyboss` text domain
- Don't use variables in translation strings
- Use placeholders with `sprintf()` for dynamic content
- Keep translatable strings simple and complete
- Avoid concatenating translated strings
- Add translator comments for context

**Example:**
```php
// Good
sprintf( __( 'Hello %s, welcome back!', 'buddyboss' ), $name );

// Bad
__( 'Hello', 'buddyboss' ) . ' ' . $name;
```

**Generate POT file:**
```bash
grunt makepot
```

#### JavaScript
- Follow **WordPress JavaScript Coding Standards**
- Use `jQuery` instead of `$` for better compatibility
- Use `function` keyword for function declarations, not arrow functions (for older browser support)
- Use `var` for variable declarations in legacy code (ES5 compatibility)
- Tab indentation (not spaces)
- Single quotes for strings
- Yoda conditions for comparisons (e.g., `if ( 'value' === variable )`)
- Space before and after parentheses in control structures

**Example:**
```javascript
// Good - WordPress style
jQuery( document ).on( 'click', '.my-button', function ( e ) {
    e.preventDefault();
    var myVar = jQuery( this ).data( 'value' );

    if ( 'expected' === myVar ) {
        // Do something
    }
});

// Avoid - Not WordPress style
$(document).on('click', '.my-button', (e) => {
    e.preventDefault();
    const myVar = $(this).data('value');

    if (myVar === 'expected') {
        // Do something
    }
});
```

## Key Files Reference

### Core Initialization
- `bp-loader.php` - Plugin entry point
- `src/bp-loader.php` - BuddyPress loader
- `src/class-buddypress.php` - Main singleton class

### Admin Framework
- `src/bp-core/bp-core-admin.php` - Admin initialization
- `src/bp-core/classes/class-bp-admin.php` - Menu structure

### ReadyLaunch JavaScript
- `src/bp-templates/bp-nouveau/readylaunch/js/buddypress-activity.js` - Activity stream
- `src/bp-templates/bp-nouveau/readylaunch/js/buddypress-activity-post-form.js` - Activity posting
- `src/bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-forums.js` - Forums (bbPress integration)

### Forums (bbPress)
- `src/bp-forums/` - bbPress integration component
- `src/bp-forums/topics/functions.php` - Topic functions
- `src/bp-forums/replies/functions.php` - Reply functions

### Build Configuration
- `package.json` - npm scripts and dependencies
- `Gruntfile.js` - Grunt build tasks
- `src/js/admin/webpack.config.js` - Webpack configuration for admin
- `composer.json` - PHP dependencies and scripts

## Development Environment

This plugin is typically developed within a WordPress installation:
- **WordPress location:** `wp-content/plugins/buddyboss-platform/`
- **Node version:** >= 14.15.0 (see `package.json`)
- **PHP version:** >= 5.6 (compatibility target), recommend 7.4+

## Security Rules

### Input Sanitization
- **Always sanitize user input** before saving to database
- Use WordPress sanitization functions:
  - `sanitize_text_field()` - For single-line text
  - `sanitize_textarea_field()` - For multi-line text
  - `sanitize_email()` - For email addresses
  - `absint()` - For positive integers
  - `intval()` - For integers
  - `sanitize_key()` - For database keys
  - `wp_kses()` or `wp_kses_post()` - For HTML content

### Output Escaping
- **Always escape output** when displaying data
- Use context-appropriate escaping:
  - `esc_html()` - For HTML content
  - `esc_attr()` - For HTML attributes
  - `esc_url()` - For URLs
  - `esc_js()` - For JavaScript strings
  - `wp_kses_post()` - For post content with allowed HTML

### Nonce Verification
- Use nonces for all form submissions and AJAX requests
- Check nonces before processing:
```php
// Create nonce
wp_nonce_field( 'bb_action_name', 'bb_nonce_field' );

// Verify nonce
if ( ! isset( $_POST['bb_nonce_field'] ) || ! wp_verify_nonce( $_POST['bb_nonce_field'], 'bb_action_name' ) ) {
    wp_die( 'Security check failed' );
}
```

### Capability Checks
- Always verify user permissions:
```php
if ( ! bp_current_user_can( 'bp_moderate' ) ) {
    return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to do that.', 'buddyboss' ), array( 'status' => 403 ) );
}
```

### SQL Injection Prevention
- Use `$wpdb->prepare()` for all database queries:
```php
global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}bp_activity WHERE user_id = %d AND type = %s",
    $user_id,
    $activity_type
) );
```

### CSRF Protection
- All state-changing operations must have nonce protection
- AJAX requests must include nonce in headers or data
- REST API uses WordPress authentication mechanisms

## Database Tables

### Table Creation Rules
- Use `dbDelta()` for creating/updating tables
- Always include charset and collation
- Follow WordPress table naming: `{$wpdb->prefix}bp_{table_name}`
- Include proper indexes for performance

**Example:**
```php
function bb_create_custom_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'bp_custom_data';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        item_id bigint(20) NOT NULL,
        type varchar(75) NOT NULL,
        value longtext NOT NULL,
        date_created datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY item_id (item_id),
        KEY type (type),
        KEY date_created (date_created)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
```

### Indexing Guidelines
- **PRIMARY KEY** on auto-increment ID
- **KEY** on columns used in WHERE clauses
- **KEY** on foreign key columns (user_id, item_id, etc.)
- **KEY** on columns used for sorting (date_created, etc.)
- **Composite indexes** for multi-column WHERE/ORDER BY queries
- Avoid over-indexing (each index adds write overhead)

### Table Naming Convention
- Core BuddyPress tables: `{$wpdb->prefix}bp_{component_name}`
- Meta tables: `{$wpdb->prefix}bp_{component_name}_meta`
- Examples:
  - `wp_bp_activity`
  - `wp_bp_activity_meta`
  - `wp_bp_groups`
  - `wp_bp_notifications`

## Hooks Pattern

### Action Hooks
BuddyBoss uses extensive action hooks for extensibility:

**Naming pattern:** `bp_{component}_{action}_{context}`

**Common patterns:**
```php
// Before action
do_action( 'bp_before_activity_save', $activity );

// After action
do_action( 'bp_activity_after_save', $activity );

// Specific context
do_action( 'bp_activity_posted_update', $content, $user_id, $activity_id );
```

**Hook into actions:**
```php
add_action( 'bp_activity_after_save', 'my_custom_function', 10, 1 );
function my_custom_function( $activity ) {
    // Custom logic
}
```

### Filter Hooks
**Naming pattern:** `bp_{component}_{what}_filter`

**Common patterns:**
```php
// Modify data before use
$activity_content = apply_filters( 'bp_get_activity_content_body', $content, $activity );

// Modify query arguments
$args = apply_filters( 'bp_activity_get_query_args', $args );

// Conditional filters
$can_post = apply_filters( 'bp_activity_user_can_post', true, $user_id );
```

### Hook Priority Best Practices
- Default priority: `10`
- Early hooks (before core): `1-9`
- Late hooks (after core): `11-100`
- Very late hooks: `100+`

## REST API

### Endpoint Structure
All REST API endpoints extend `WP_REST_Controller`:

```php
class BB_REST_Custom_Endpoint extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
        $this->rest_base = 'custom';
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
                'schema' => array( $this, 'get_item_schema' ),
            )
        );
    }

    public function get_items_permissions_check( $request ) {
        // Permission logic
        return true;
    }

    public function get_items( $request ) {
        // Return WP_REST_Response or WP_Error
    }
}
```

### REST API Conventions
- Namespace: `buddyboss/v1`
- Always implement permission callbacks
- Return `WP_REST_Response` for success
- Return `WP_Error` for errors with proper HTTP status codes
- Support batch operations where appropriate
- Implement schema for endpoints

## Asset Management

### Script Enqueuing
```php
// Enqueue script with dependencies
wp_enqueue_script(
    'bb-custom-script',
    plugins_url( 'js/custom.js', __FILE__ ),
    array( 'jquery', 'bp-nouveau' ), // Dependencies
    BP_PLATFORM_VERSION,
    true // Load in footer
);

// Localize script data
wp_localize_script(
    'bb-custom-script',
    'bbCustomData',
    array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'bb_custom_nonce' ),
    )
);
```

### Style Enqueuing
```php
wp_enqueue_style(
    'bb-custom-style',
    plugins_url( 'css/custom.css', __FILE__ ),
    array( 'bp-nouveau' ), // Dependencies
    BP_PLATFORM_VERSION
);
```

### Asset Loading Best Practices
- Use `BP_PLATFORM_VERSION` for cache busting
- Specify dependencies to ensure load order
- Load scripts in footer when possible (`true` parameter)
- Minify assets for production
- Use conditional loading (only load on relevant pages)
- RTL support: Generate RTL CSS with `grunt rtlcss`

## Component Pattern

Each component follows a consistent structure:

### Component Class
```php
class BP_Custom_Component extends BP_Component {

    public function __construct() {
        parent::start(
            'custom',                    // Component ID
            __( 'Custom', 'buddyboss' ), // Component name
            BP_PLUGIN_DIR,               // Plugin directory
            array(
                'adminbar_myaccount_order' => 50,
            )
        );
    }

    public function includes( $includes = array() ) {
        $includes = array(
            'functions',
            'template',
            'filters',
            'actions',
        );
        parent::includes( $includes );
    }

    public function setup_globals( $args = array() ) {
        // Setup component globals
        parent::setup_globals( $args );
    }
}
```

### Component Files
- `bp-{component}-loader.php` - Component initialization
- `bp-{component}-functions.php` - Public API functions
- `bp-{component}-template.php` - Template tags
- `bp-{component}-filters.php` - Filter hooks
- `bp-{component}-actions.php` - Action hooks
- `classes/` - Component classes
- `screens/` - Screen handlers

## Email System

BuddyBoss uses a token-based email system for all notifications and communications.

### Email Architecture
- **Email Post Type:** Emails are stored as `bp-email` custom post type
- **Email Templates:** Customizable HTML/plain text templates
- **Email Tokens:** Dynamic placeholders like `{{user.name}}`, `{{activity.url}}`
- **Email Queue:** Background queue for batch email processing

### Sending Emails
```php
// Create email object
$email = new BP_Email( 'activity-comment' ); // Email type slug

// Set recipients
$email->set_to( $user_id );
$email->set_cc( $cc_users );

// Set tokens for dynamic content
$email->set_tokens( array(
    'activity.url'   => bp_activity_get_permalink( $activity_id ),
    'commenter.name' => bp_core_get_user_displayname( $commenter_id ),
    'comment.text'   => $comment_content,
) );

// Send email
bp_send_email( $email );
```

### Email Queue System
- Uses `BP_Email_Queue` class for batching
- Prevents email flooding
- Background processing via WP Cron
- Handles rate limiting

**Check if queue enabled:**
```php
if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() ) {
    // Queue is enabled
}
```

### Creating Custom Email Types
1. Register email type in admin
2. Define email tokens
3. Create template with tokens
4. Send via `BP_Email` class

### Email Tokens
- `{{user.name}}` - User display name
- `{{user.email}}` - User email
- `{{activity.url}}` - Activity permalink
- `{{group.name}}` - Group name
- Custom tokens via filters

## Background Processes

BuddyBoss uses background processes for long-running tasks and migrations.

### Background Process Classes
- `BB_Background_Process` - Base class for async operations
- `BB_Background_Updater` - For database migrations
- Extends WordPress `WP_Async_Request`

### Creating Background Process
```php
class BB_Custom_Migration extends BB_Background_Process {

    protected $action = 'custom_migration';

    protected function task( $item ) {
        // Process single item
        // Perform migration logic

        // Return false when done, or $item to keep in queue
        return false;
    }

    protected function complete() {
        parent::complete();
        // Run after all items processed
        update_option( 'bb_custom_migration_complete', true );
    }
}

// Usage
$process = new BB_Custom_Migration();
$process->push_to_queue( $item_1 );
$process->push_to_queue( $item_2 );
$process->save()->dispatch();
```

### Background Process Patterns
- **Queue items:** `push_to_queue( $data )`
- **Save queue:** `save()`
- **Dispatch:** `dispatch()` - Starts processing
- **Task method:** Process one item at a time
- **Complete method:** Called when all items done

### Process Management
- Processes run via WordPress cron
- Can be monitored via `BB_BG_Process_Log` class
- Progress tracking available
- Cleanup old migrations with exclusion logic

### Best Practices
- Keep `task()` method fast (process single item)
- Return `false` to remove from queue
- Return `$item` to keep processing
- Use `complete()` for final cleanup
- Log progress for debugging

## Major BuddyBoss Features

### Reactions System
**Location:** `src/bp-activity/bb-activity-reactions.php`, `class-bb-reaction.php`

Emoji reactions on activities (Like, Love, Laugh, etc.)

```php
// Check if reactions enabled
if ( function_exists( 'bb_load_reaction' ) ) {
    // Reactions available
}

// Get activity reactions
$reactions = bb_get_activity_reactions( $activity_id );

// Add reaction
bb_add_activity_reaction( $activity_id, $user_id, $reaction_type );
```

### Follow System
**Location:** `src/bp-activity/classes/class-bp-activity-follow.php`

Users can follow other users for activity updates.

```php
// Check if user follows another
if ( bp_is_following( array( 'leader_id' => $leader_id, 'follower_id' => $follower_id ) ) ) {
    // User is following
}

// Start following
bp_start_following( array( 'leader_id' => $leader_id, 'follower_id' => $follower_id ) );

// Stop following
bp_stop_following( array( 'leader_id' => $leader_id, 'follower_id' => $follower_id ) );

// Get followers
$followers = bp_get_follower_ids( array( 'user_id' => $user_id ) );
```

### Subscriptions System
**Location:** `src/bp-performance/classes/integrations/class-bb-subscriptions.php`

Users can subscribe to content updates (groups, forums, etc.)

```php
// Subscribe to item
bb_subscribe_to_item( $user_id, $item_id, $item_type );

// Check subscription
if ( bb_is_subscribed( $user_id, $item_id, $item_type ) ) {
    // User is subscribed
}

// Unsubscribe
bb_unsubscribe_from_item( $user_id, $item_id, $item_type );
```

### Moderation System
**Location:** `src/bp-moderation/`

Content moderation tools for blocking/reporting.

```php
// Check if content is moderated
if ( bp_moderation_is_content_hidden( $item_id, $item_type ) ) {
    // Content is hidden
}

// Report content
bp_moderation_report( array(
    'item_id'   => $item_id,
    'item_type' => $item_type,
    'user_id'   => $reporter_id,
) );
```

## Template Tags

Template tags provide data for theme templates:

### Naming Convention
- Check functions: `bp_{component}_has_{thing}()`
- Get functions: `bp_{component}_get_{thing}()`
- Display functions: `bp_{component}_{thing}()`

**Example:**
```php
// Check if has activities
if ( bp_has_activities() ) :

    // Loop through activities
    while ( bp_activities() ) : bp_the_activity();

        // Display activity content
        bp_activity_content_body();

    endwhile;

endif;
```

### Template Tag Pattern
```php
// Display function (echoes)
function bp_custom_display_name() {
    echo bp_get_custom_display_name();
}

// Get function (returns)
function bp_get_custom_display_name() {
    $name = 'Custom Name';
    return apply_filters( 'bp_get_custom_display_name', $name );
}
```

## Widgets & Blocks

### WordPress Widgets

BuddyBoss includes many built-in widgets for sidebars.

**Available Widgets:**
- `BP_Core_Members_Widget` - Display members list
- `BP_Core_Recently_Active_Widget` - Recently active members
- `BP_Core_Whos_Online_Widget` - Currently online members
- `BP_Core_Friends_Widget` - Friends list
- `BP_Core_Follow_Following_Widget` - Users being followed
- `BP_Core_Follow_Follower_Widget` - Followers list
- `BB_Core_Follow_My_Network_Widget` - Network connections
- `BP_Groups_Widget` - Groups list
- `BP_Blogs_Recent_Posts_Widget` - Recent blog posts
- `BP_XProfile_Profile_Completion_Widget` - Profile completion progress

**Widget Registration Pattern:**
```php
function bb_register_custom_widget() {
    register_widget( 'BB_Custom_Widget' );
}
add_action( 'bp_register_widgets', 'bb_register_custom_widget' );

class BB_Custom_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bb_custom_widget',
            __( 'Custom Widget', 'buddyboss' ),
            array(
                'description' => __( 'Display custom content', 'buddyboss' ),
            )
        );
    }

    public function widget( $args, $instance ) {
        // Widget output
        echo $args['before_widget'];
        echo $args['before_title'] . $instance['title'] . $args['after_title'];
        // Widget content
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        // Widget admin form
    }

    public function update( $new_instance, $old_instance ) {
        // Save widget settings
        return $new_instance;
    }
}
```

### Gutenberg Blocks

**Block Location:** `src/bp-core/blocks/`

**Available Blocks:**
- **ReadyLaunch Header Block** - Header navigation for ReadyLaunch mode

**Block Registration Pattern:**
```javascript
// Block source: src/js/blocks/bp-core/{block-name}/index.js
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'buddyboss/readylaunch-header', {
    title: 'ReadyLaunch Header',
    icon: 'admin-customizer',
    category: 'buddyboss',
    edit: EditComponent,
    save: SaveComponent,
} );
```

**Build blocks:**
```bash
npm run build:blocks
```

## Integrations Architecture

BuddyBoss supports integration with third-party plugins using a consistent pattern.

### Integration Pattern

**Base Class:** `BP_Integration` (extends `BP_Component`)

**Available Integrations:**
- **LearnDash** - LMS integration for courses in groups
- **Pusher** - Real-time notifications
- **reCAPTCHA** - Spam protection
- **BuddyBoss App** - Mobile app connectivity

### Creating Integration

```php
class BB_Custom_Integration extends BP_Integration {

    public function __construct() {
        $this->start(
            'custom-integration',
            __( 'Custom Integration', 'buddyboss' ),
            'custom',
            array(
                'required_plugin' => array(
                    array(
                        'file' => 'custom-plugin/custom-plugin.php',
                        'name' => 'Custom Plugin',
                    ),
                ),
            )
        );
    }

    public function includes( $includes = array() ) {
        // Load integration files
        $includes = array(
            'functions',
            'filters',
            'actions',
        );
        parent::includes( $includes );
    }

    public function setup_actions() {
        // Hook into integration lifecycle
        parent::setup_actions();
    }
}

// Register integration
function bb_register_custom_integration() {
    buddypress()->integrations['custom'] = new BB_Custom_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_custom_integration' );
```

### Integration Admin Tab

Create admin settings tab for integration:

```php
class BB_Custom_Admin_Integration_Tab extends BP_Admin_Integration_tab {

    public function initialize() {
        $this->tab_order = 50;
        $this->intro_template = 'custom-integration-intro';
    }

    public function register_fields() {
        $this->add_section( 'bb_custom_settings', __( 'Custom Settings', 'buddyboss' ) );

        $this->add_field( 'bb_custom_enabled', __( 'Enable Integration', 'buddyboss' ), 'checkbox' );
        $this->add_field( 'bb_custom_api_key', __( 'API Key', 'buddyboss' ), 'text' );
    }
}
```

### Integration Detection

```php
// Check if integration is active
if ( bp_is_active( 'custom-integration' ) ) {
    // Integration is available
}

// Check required plugin
if ( class_exists( 'Custom_Plugin_Class' ) ) {
    // Third-party plugin is installed
}
```

## Performance

### Caching
Use WordPress transients and object cache:

```php
// Get from cache
$data = wp_cache_get( $cache_key, 'bp_custom' );

if ( false === $data ) {
    // Generate data
    $data = expensive_operation();

    // Store in cache
    wp_cache_set( $cache_key, $data, 'bp_custom', 3600 );
}

// Invalidate cache when data changes
wp_cache_delete( $cache_key, 'bp_custom' );
```

### Database Query Optimization
- Use indexed columns in WHERE clauses
- Limit result sets with `LIMIT`
- Avoid `SELECT *`, specify needed columns
- Use `$wpdb->get_results()` with `ARRAY_A` for large datasets
- Use `wp_cache_get()` before database queries

### Performance Best Practices
- Lazy load images and media
- Minimize database queries in loops
- Use transients for expensive operations
- Implement pagination for large datasets
- Use `wp_enqueue_script()` with proper dependencies
- Minimize AJAX requests, batch when possible

## WP-CLI Compatibility

### Detection
```php
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    // WP-CLI specific code
}
```

### Best Practices
- Avoid outputting HTML in WP-CLI context
- Use `WP_CLI::log()` for output instead of `echo`
- Disable progress bars and visual elements
- Return data structures instead of formatted output

## Testing

### Development Testing
- **Enable `WP_DEBUG`** in `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

- **Check PHP error logs** in `wp-content/debug.log`
- **Test with Query Monitor** plugin for performance analysis

### Compatibility Requirements
- **PHP version:** 5.6+ (compatibility target), 7.4+ (recommended)
- **WordPress version:** 6.0+ (minimum)
- Test on multiple PHP versions: 7.4, 8.0, 8.1, 8.2
- Test on multiple WordPress versions

### Pre-Commit Checklist
- [ ] Run `composer lint-php` - No PHPCS errors
- [ ] Run `composer lint-php-fix` - Auto-fix formatting
- [ ] Run `npm run lint-js` - No JavaScript errors
- [ ] Run `composer test` - All PHPUnit tests pass
- [ ] Test with `WP_DEBUG` enabled - No PHP warnings/notices
- [ ] Test in ReadyLaunch mode if applicable
- [ ] Test with legacy mode (bp-legacy templates)
- [ ] Verify database queries are using `$wpdb->prepare()`
- [ ] Verify all output is properly escaped
- [ ] Verify all input is sanitized

### Browser Testing
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Common Pitfalls

### ReadyLaunch Emoji Handling
When working with MediumEditor in ReadyLaunch:
- **DO NOT** use raw `editor.getContent()` for form submissions
- **ALWAYS** convert emoji `<img>` tags to actual emoji characters before submitting
- This applies to: activity posts, comments, forum topics, forum replies, and paste events

### Template Wrappers
ReadyLaunch modals require `.bb-rl-screen-content` wrapper:
- Missing wrapper = modals won't open correctly
- Check user profile, group activity, and single activity templates

### JavaScript Formatting
If formatters/linters revert emoji conversion code:
- Check `.prettierrc`, `.eslintrc`, or editor formatting settings
- Emoji conversion code is critical for functionality
- May need to disable auto-formatting for specific sections
