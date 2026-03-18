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

	// Admin/AJAX-only: AJAX handlers, meta field registry, settings page, feature settings
	// (panels, sections, fields), and icon registry. Skip on frontend for performance —
	// frontend only needs the Feature Registry + Loader for conditional component loading.
	if ( is_admin() || wp_doing_ajax() ) {
		// Admin Meta Field Registry (component-based).
		if ( ! class_exists( 'BB_Admin_Meta_Field_Registry' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-meta-field-registry.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-settings-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-settings-ajax.php';
		}

		if ( bp_is_active( 'activity' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-activity-admin-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-activity-admin-ajax.php';
		}

		if ( bp_is_active( 'groups' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-groups-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-groups-ajax.php';
		}

		if ( bp_is_active( 'forums' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-forums-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-forums-ajax.php';
		}

		if ( bp_is_active( 'forums' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topics-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topics-ajax.php';
		}

		if ( bp_is_active( 'forums' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topic-tags-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topic-tags-ajax.php';
		}

		if ( bp_is_active( 'forums' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-replies-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-replies-ajax.php';
		}

		// Profile/Member Types AJAX handler (only when xprofile component is active).
		if ( bp_is_active( 'xprofile' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-member-types-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-member-types-ajax.php';
		}

		// Profile Fields AJAX handler (only when xprofile component is active).
		if ( bp_is_active( 'xprofile' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-fields-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-fields-ajax.php';
		}

		// Email Templates AJAX handler (always loaded — email templates are a core feature).
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-email-templates-admin-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-email-templates-admin-ajax.php';
		}

		// Profile Search AJAX handler (only when xprofile component is active).
		// Also ensure profile search module is loaded — it may not be when the
		// toggle is OFF because bp_core_load_profile_search() returns early.
		if ( bp_is_active( 'xprofile' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-search-ajax.php' ) ) {
			$bps_start = buddypress()->plugin_dir . 'bp-core/profile-search/bps-start.php';
			if ( ! function_exists( 'bp_profile_search_main_form' ) && file_exists( $bps_start ) ) {
				if ( ! defined( 'BPS_VERSION' ) ) {
					define( 'BPS_VERSION', BP_PLATFORM_VERSION );
				}
				require_once $bps_start;
			}
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-search-ajax.php';
		}

		// Admin settings page (menu registration, render function).
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php';
		}

		// Feature settings registration (panels, sections, fields).
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-activity.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-activity.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-groups.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-groups.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-forums.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-forums.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-media.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-media.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-messages.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-messages.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-invites.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-invites.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-emails.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-emails.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-search.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-search.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-members.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-members.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-notifications.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-notifications.php';
		}

		// Icon registry.
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/classes/class-bb-icon-registry.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-icon-registry.php';
		}

		bb_icon_registry();
	}
}

add_action( 'bp_loaded', 'bb_admin_settings_init', 4 );

/**
 * Initialize the Integration Bridge early.
 *
 * Must run at priority 2 (before bp_setup_integrations at priority 3)
 * so the bb_integration_is_activated filter is registered before
 * BP_Integration::is_activated() is called during integration setup.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_integration_bridge_early_init() {
	if ( ! class_exists( 'BB_Integration_Bridge' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-integration-bridge.php';
	}

	// Initialize the bridge singleton (registers hooks).
	bb_integration_bridge();

	/**
	 * Fires after the Integration Bridge is initialized.
	 *
	 * Plugins can use this to register managed integrations via
	 * bb_integration_bridge()->register_managed_integration().
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_integration_bridge_init' );
}

add_action( 'bp_loaded', 'bb_integration_bridge_early_init', 2 );

// Clear feature discovery cache when plugins are activated, deactivated, or upgraded.
add_action( 'activated_plugin', array( 'BB_Feature_Autoloader', 'bb_clear_feature_discovery_cache' ) );
add_action( 'deactivated_plugin', array( 'BB_Feature_Autoloader', 'bb_clear_feature_discovery_cache' ) );
add_action( 'upgrader_process_complete', array( 'BB_Feature_Autoloader', 'bb_clear_feature_discovery_cache' ) );

/**
 * Reverse-sync legacy Components page saves to bb-active-features.
 *
 * When an admin uses the legacy BuddyBoss > Components page, it updates
 * bp-active-components directly. This hook keeps bb-active-features in sync
 * so both options reflect the same activation state.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $old_value Previous option value.
 * @param mixed $new_value Updated option value.
 */
function bb_reverse_sync_components_to_features( $old_value, $new_value ) {
	// Prevent infinite recursion: skip if we're inside a feature toggle from Settings 2.0.
	static $syncing = false;
	if ( $syncing ) {
		return;
	}

	// Only run if Feature Registry is loaded.
	if ( ! function_exists( 'bb_feature_registry' ) ) {
		return;
	}

	$syncing  = true;
	$registry = bb_feature_registry();

	$active_features  = bp_get_option( 'bb-active-features', array() );
	$features_changed = false;
	$new_components   = is_array( $new_value ) ? $new_value : array();

	// Check each registered feature and sync its state from bp-active-components.
	// bb_get_features() already returns full feature data keyed by ID, so use it directly.
	foreach ( $registry->bb_get_features( array( 'status' => 'all' ) ) as $feature_id => $feature ) {
		// Determine component IDs that correspond to this feature.
		$component_ids = array( $feature_id );
		if ( ! empty( $feature['components'] ) && is_array( $feature['components'] ) ) {
			$component_ids = $feature['components'];
		}

		// Skip features that have no matching key in bp-active-components.
		// This covers pure Settings 2.0 features (e.g. reactions) that don't
		// map to a legacy component — there is nothing to derive state from.
		$has_component_key = false;
		foreach ( $component_ids as $component_id ) {
			if ( array_key_exists( $component_id, $new_components ) || array_key_exists( $component_id, is_array( $old_value ) ? $old_value : array() ) ) {
				$has_component_key = true;
				break;
			}
		}
		if ( ! $has_component_key ) {
			continue;
		}

		// Feature is active if any of its components are active.
		$is_component_active = false;
		foreach ( $component_ids as $component_id ) {
			if ( ! empty( $new_components[ $component_id ] ) ) {
				$is_component_active = true;
				break;
			}
		}

		$was_active = ! empty( $active_features[ $feature_id ] );

		if ( $is_component_active && ! $was_active ) {
			$active_features[ $feature_id ] = 1;
			$features_changed               = true;
		} elseif ( ! $is_component_active && $was_active ) {
			$active_features[ $feature_id ] = 0;
			$features_changed               = true;
		}
	}

	if ( $features_changed ) {
		bp_update_option( 'bb-active-features', $active_features );
		$registry->bb_clear_feature_caches();
	}

	$syncing = false;
}
add_action( 'update_option_bp-active-components', 'bb_reverse_sync_components_to_features', 10, 2 );

// Also hook into add_option for first-time creation of bp-active-components (fresh install).
// add_option_{$option} passes ( $option_name, $value ) not ( $old_value, $new_value ).
add_action(
	'add_option_bp-active-components',
	function ( $option_name, $value ) {
		bb_reverse_sync_components_to_features( array(), $value );
	},
	10,
	2
);

/**
 * Fallback sanitize callback for access control fields when Pro is not active.
 *
 * Pro provides bb_sanitize_access_control_field() with full validation logic.
 * This fallback handles the case where Pro is not installed/active, ensuring
 * the value is safely sanitized as an array structure rather than being
 * passed through sanitize_text_field() which is inappropriate for arrays.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The raw input value (JSON string or array).
 *
 * @return array Sanitized array, or empty array if input is invalid.
 */
function bb_sanitize_access_control_fallback( $value ) {

	// Handle JSON-encoded string from frontend.
	if ( is_string( $value ) ) {
		$value = json_decode( $value, true );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	return map_deep( $value, 'sanitize_text_field' );
}
