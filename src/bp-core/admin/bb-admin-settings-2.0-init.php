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
	if ( ! class_exists( 'BB_Integration_Bridge' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-integration-bridge.php';
	}

	// Initialize Component Bridge early (hooks into bp_optional_components filter).
	// This must happen before Feature Registry so it can capture legacy components.
	bb_component_bridge();

	// Initialize Integration Bridge (hooks into BP_Integration::is_activated filter).
	// This allows integrations to be controlled via the feature system.
	bb_integration_bridge();

	// Register managed integrations (these can be enabled/disabled via feature cards).
	bb_integration_bridge()->register_managed_integration( 'learndash', 'learndash' );
	bb_integration_bridge()->register_managed_integration( 'pusher', 'pusher' );
	bb_integration_bridge()->register_managed_integration( 'recaptcha', 'recaptcha' );

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

	// Load feature registrations from core admin.
	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-features.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-features.php';
	}

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-activity.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-activity.php';
	}

	if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-groups.php' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-2.0-groups.php';
	}

	// Note: All features are registered centrally in:
	// - bb-admin-settings-2.0-features.php (Community, Add-ons, Integrations)
	// - bb-admin-settings-2.0-activity.php (Activity Feeds with side panels and fields)
	// - bb-admin-settings-2.0-groups.php (Social Groups with side panels and fields)

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

/**
 * Migrate component statuses to bb-active-features on first load.
 *
 * This ensures backward compatibility when upgrading from component-based
 * architecture to feature-based architecture.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_migrate_components_to_features() {
	// Check if migration has already been done.
	$migration_done = bp_get_option( 'bb-features-migration-done', false );

	if ( $migration_done ) {
		return;
	}

	// Get existing component statuses.
	$active_components = bp_get_option( 'bp-active-components', array() );

	if ( empty( $active_components ) ) {
		// No components to migrate - mark migration as done.
		bp_update_option( 'bb-features-migration-done', true );
		return;
	}

	// Get current active features (if any already exist).
	$active_features = bp_get_option( 'bb-active-features', array() );

	// Migrate each active component to active features.
	foreach ( $active_components as $component_id => $status ) {
		// Only migrate if not already set in features.
		if ( ! isset( $active_features[ $component_id ] ) ) {
			$active_features[ $component_id ] = ! empty( $status ) ? 1 : 0;
		}
	}

	// Save migrated features.
	bp_update_option( 'bb-active-features', $active_features );

	// Mark migration as done.
	bp_update_option( 'bb-features-migration-done', true );

	/**
	 * Fires after components have been migrated to features.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $active_features   The migrated features.
	 * @param array $active_components The original components.
	 */
	do_action( 'bb_components_migrated_to_features', $active_features, $active_components );
}
add_action( 'bp_loaded', 'bb_migrate_components_to_features', 3 ); // Before settings 2.0 init (priority 4).
