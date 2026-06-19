<?php
/**
 * Bootstrap for {@see BB_Debug_Asset_Fetcher}.
 *
 * Loaded from {@see bp_setup_core()} so the hooks are wired before
 * `admin_init` fires. Mirrors the pattern used by `bb-admin-settings-init.php`:
 * a small loader file requires the class and registers the entrypoint hook,
 * keeping `bp-core-loader.php` itself uncluttered.
 *
 * The fetcher's `is_active()` guard means this file is cheap to include even
 * on production sites — the singleton is built, hooks register, but
 * `maybe_fetch()` exits in microseconds when WP_DEBUG/SCRIPT_DEBUG are off.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 3.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the fetcher class and register its hooks.
 *
 * @since BuddyBoss 3.0.3
 *
 * @return void
 */
function bb_debug_asset_fetcher_init() {
	if ( ! class_exists( 'BB_Debug_Asset_Fetcher' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-debug-asset-fetcher.php';
	}
	BB_Debug_Asset_Fetcher::instance()->bootstrap();
}
add_action( 'bp_loaded', 'bb_debug_asset_fetcher_init', 3 );
