<?php
/**
 * BuddyBoss Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-core component.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_core() {
	bboss()->core = new BBoss_Core();

	// Load Admin Settings 2.0 initialization early.
	if ( file_exists( bboss()->plugin_dir . 'bboss-core/admin/bboss-admin-settings-init.php' ) ) {
		require_once bboss()->plugin_dir . 'bboss-core/admin/bboss-admin-settings-init.php';
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_core' ) && ! function_exists( 'bp_setup_core' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_core()}.
	 *
	 * @deprecated Use bboss_setup_core() instead.
	 */
	function bp_setup_core() {
		return bboss_setup_core();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_loaded', 'bp_setup_core', 0 );
