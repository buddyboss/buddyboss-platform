<?php
/**
 * BuddyBoss Admin Settings 2.0 Initialization
 *
 * Initializes Feature Registry, Icon Registry, REST API controllers,
 * and Settings History for the new admin architecture.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize Feature Registry and Icon Registry.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_init() {
	// Ensure class files are loaded first.
	if ( ! class_exists( 'BB_Feature_Registry' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-feature-registry.php';
	}
	if ( ! class_exists( 'BB_Icon_Registry' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-icon-registry.php';
	}
	if ( ! class_exists( 'BB_Settings_History' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-settings-history.php';
	}
	if ( ! class_exists( 'BB_REST_Response' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-rest-response.php';
	}
	if ( ! class_exists( 'BB_REST_Dashboard_Controller' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-rest-dashboard-controller.php';
	}
	if ( ! class_exists( 'BB_Feature_Autoloader' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-feature-autoloader.php';
	}
	if ( ! class_exists( 'BB_Feature_Loader' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-feature-loader.php';
	}
	if ( ! class_exists( 'BB_Component_Bridge' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-component-bridge.php';
	}

	// Initialize Component Bridge early (hooks into bp_optional_components filter).
	// This must happen before Feature Registry so it can capture legacy components.
	bb_component_bridge();

	// Initialize Feature Registry (singleton, hooks into bp_loaded).
	bb_feature_registry();

	// Initialize Feature Loader (hooks into bb_after_register_features).
	bb_feature_loader();

	// Initialize Icon Registry (singleton, hooks into bp_loaded).
	bb_icon_registry();

	// Initialize Settings History (singleton).
	bb_settings_history();

	// Load migration system.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-migration.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-migration.php';
	}

	// Load admin page callback.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-page.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-page.php';
	}

	// Load AJAX handlers.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/class-bb-admin-settings-ajax.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/class-bb-admin-settings-ajax.php';
	}
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/class-bb-admin-activity-ajax.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/class-bb-admin-activity-ajax.php';
	}
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/class-bb-admin-groups-ajax.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/class-bb-admin-groups-ajax.php';
	}

	// Load feature registrations.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-features.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-features.php';
	}

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-activity.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-activity.php';
	}

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-groups.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-groups.php';
	}

	// Register feature autoloader for code compartmentalization.
	if ( class_exists( 'BB_Feature_Autoloader' ) ) {
		BB_Feature_Autoloader::register();
	}
}
add_action( 'bp_loaded', 'bb_admin_settings_2_0_init', 4 ); // Before feature registration (priority 5).

/**
 * Register REST API controllers.
 *
 * Note: Most controllers have been migrated to AJAX for better security and performance.
 * See class-bb-admin-settings-ajax.php, class-bb-admin-activity-ajax.php, and class-bb-admin-groups-ajax.php
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_rest_api_init() {
	// Register Dashboard Controller.
	$dashboard_controller = new BB_REST_Dashboard_Controller();
	$dashboard_controller->register_routes();
}
add_action( 'bp_rest_api_init', 'bb_admin_settings_2_0_rest_api_init', 10 );
// Fallback: Also register on rest_api_init in case bp_rest_api_init doesn't fire.
add_action( 'rest_api_init', 'bb_admin_settings_2_0_rest_api_init', 20 );

/**
 * Initialize Settings History table on activation.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_activate() {
	// Create settings history table.
	do_action( 'bb_settings_history_init' );
}
// Note: Activation hook should be registered in bp-loader.php or main plugin file.
// This will be called via 'bb_plugin_activated' action or similar.
add_action( 'bb_settings_history_init', array( 'BB_Settings_History', 'maybe_create_table' ) );

