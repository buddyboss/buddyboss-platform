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
function bp_setup_core() {
	buddypress()->core = new BP_Core();

	// Load Admin Settings 2.0 initialization early.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-init.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-init.php';
	}
}
add_action( 'bp_loaded', 'bp_setup_core', 0 );
