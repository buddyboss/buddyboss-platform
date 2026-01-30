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

	if ( ! class_exists( 'BB_Feature_Autoloader' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-feature-autoloader.php';
	}

	if ( ! class_exists( 'BB_Feature_Registry' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-feature-registry.php';
	}

	if ( ! class_exists( 'BB_Feature_Loader' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-feature-loader.php';
	}

	BB_Feature_Autoloader::bb_register();

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-features.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-features.php';
	}

	BB_Feature_Autoloader::bb_discover_features();

	bb_feature_registry();
	bb_feature_loader();

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-settings-ajax.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-settings-ajax.php';
	}

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php';
	}
}

add_action( 'bp_loaded', 'bb_admin_settings_init', 4 );
