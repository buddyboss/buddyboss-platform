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
add_action( 'bp_loaded', 'bboss_setup_core', 0 );
