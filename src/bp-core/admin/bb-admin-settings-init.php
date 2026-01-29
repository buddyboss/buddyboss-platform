<?php
/**
 * BuddyBoss Admin Settings Initialization.
 *
 * Initializes Feature Registry, Icon Registry, REST API controllers,
 * and Settings History for the new admin architecture.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize BuddyBoss Admin Settings.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_init() {

    // Load admin settings initialization.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php';
	}
}

add_action( 'bp_loaded', 'bb_admin_settings_init', 4 );
