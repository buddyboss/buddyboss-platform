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

		// Email Templates AJAX handlers (emails feature is required — always active).
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-email-templates-admin-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-email-templates-admin-ajax.php';
		}

		// Email Invites AJAX handlers (only when invites component is active).
		if ( bp_is_active( 'invites' ) && file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-invites-ajax.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-invites-ajax.php';
		}

		// Forums AJAX handlers (only when forums component is active).
		if ( bp_is_active( 'forums' ) ) {
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-forums-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-forums-ajax.php';
			}
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topics-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topics-ajax.php';
			}
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topic-tags-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-topic-tags-ajax.php';
			}
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-replies-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-replies-ajax.php';
			}
		}

		// Profile AJAX handlers (only when xprofile component is active).
		if ( bp_is_active( 'xprofile' ) ) {
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-member-types-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-member-types-ajax.php';
			}
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-fields-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-fields-ajax.php';
			}

			// Also ensure profile search module is loaded — it may not be when the
			// toggle is OFF because bp_core_load_profile_search() returns early.
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-search-ajax.php' ) ) {
				$bps_start = buddypress()->plugin_dir . 'bp-core/profile-search/bps-start.php';
				if ( ! function_exists( 'bp_profile_search_main_form' ) && file_exists( $bps_start ) ) {
					if ( ! defined( 'BPS_VERSION' ) ) {
						define( 'BPS_VERSION', BP_PLATFORM_VERSION );
					}
					require_once $bps_start;
				}
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-search-ajax.php';
			}
		}

		if ( bp_is_active( 'moderation' ) ) {
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-moderation-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-moderation-ajax.php';
			}
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-flagged-members-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-flagged-members-ajax.php';
			}
			if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-reported-content-ajax.php' ) ) {
				require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-reported-content-ajax.php';
			}
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

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-account.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-account.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-notifications.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-notifications.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-moderation.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-moderation.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-registration.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-registration.php';
		}

		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-advanced.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-advanced.php';
		}

		// Icon registry.
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/classes/class-bb-icon-registry.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-icon-registry.php';
		}

		bb_icon_registry();

		// Placeholder features provider for unregistered add-ons/integrations.
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/admin/bb-admin-placeholder-features.php' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-placeholder-features.php';
		}
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

/**
 * Flush every Settings 2.0 cache layer in response to a plugin-lifecycle event.
 *
 * When a plugin is activated, deactivated, or upgraded, any of these caches
 * may silently hold stale data:
 *
 *  1. **Feature discovery paths** — `bb_feature_config_paths_*` transient.
 *     A newly-active plugin may ship `bb-features/*` configs that the cache
 *     has not seen; a deactivated plugin's configs must be dropped.
 *  2. **Feature registry** — in-memory sorted caches, per-feature transients
 *     (`bb_feature_{id}`), and the static option-cache dirty flag consulted
 *     by `bb_is_feature_active()`.
 *  3. **Settings search index** — `bb_settings_search_index` transient.
 *     Built from the registered feature/panel/section/field graph; a plugin
 *     that registers or removes features invalidates the index.
 *  4. **Placeholder features catalog** — `bb_placeholder_features_data_v_*`
 *     transient plus the `bb_placeholder_features_data_stale` option. A
 *     plugin install/activate/deactivate changes the `plugin_status` of
 *     catalog entries, so cached upgrade cards become wrong.
 *
 * The registry getters rebuild lazily, so clearing here simply forces the
 * next admin AJAX / page load to re-read fresh state. No request-time cost.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_flush_feature_caches_on_plugin_change() {
	// 1. Feature discovery paths (filesystem scan cache).
	if ( class_exists( 'BB_Feature_Autoloader' ) ) {
		BB_Feature_Autoloader::bb_clear_feature_discovery_cache();
	}

	// 2. Feature registry (sorted caches, per-feature transients, active-state flag).
	if ( function_exists( 'bb_feature_registry' ) ) {
		bb_feature_registry()->bb_clear_feature_caches();
	}

	// 3. Settings search index — cleared by bb_clear_feature_caches() above,
	//    but deleted here too so this function is safe to call standalone
	//    (e.g. from a CLI command that doesn't boot the registry).
	delete_transient( 'bb_settings_search_index' );

	// 4. Placeholder features catalog — plugin_status fields depend on
	//    is_plugin_active() results which just changed.
	if ( function_exists( 'bb_placeholder_features_transient_key' ) ) {
		delete_transient( bb_placeholder_features_transient_key() );
	}
	// Always delete the legacy unversioned key too, for clean upgrades.
	delete_transient( 'bb_placeholder_features_data' );

	// Schedule a background refresh so the next admin AJAX call returns data.
	if ( function_exists( 'bb_schedule_placeholder_features_refresh' ) ) {
		bb_schedule_placeholder_features_refresh();
	}

	/**
	 * Fires after Settings 2.0 caches are flushed in response to a plugin
	 * lifecycle event. Extensions that maintain their own Settings 2.0 caches
	 * (e.g. Pro's per-feature memoizations) can hook here to invalidate.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_feature_caches_flushed' );
}

// Plugin lifecycle: activate / deactivate / install / update / delete all
// reshape the set of features and placeholder states. Each hook runs a full
// flush — the cost is one `delete_transient` per cache layer plus a light
// in-memory reset, cheap relative to a plugin install.
add_action( 'activated_plugin', 'bb_flush_feature_caches_on_plugin_change' );
add_action( 'deactivated_plugin', 'bb_flush_feature_caches_on_plugin_change' );
add_action( 'upgrader_process_complete', 'bb_flush_feature_caches_on_plugin_change' );
add_action( 'deleted_plugin', 'bb_flush_feature_caches_on_plugin_change' );

// Network-admin (multisite) plugin lifecycle hooks.
add_action( 'network_admin_activated_plugin', 'bb_flush_feature_caches_on_plugin_change' );
add_action( 'network_admin_deactivated_plugin', 'bb_flush_feature_caches_on_plugin_change' );

// Also flush when a user flips the "active_plugins" option directly — covers
// WP-CLI `wp plugin activate/deactivate` which bypasses activated_plugin on
// some paths, and programmatic `update_option( 'active_plugins', ... )` calls.
add_action( 'update_option_active_plugins', 'bb_flush_feature_caches_on_plugin_change' );
add_action( 'add_option_active_plugins', 'bb_flush_feature_caches_on_plugin_change' );
add_action( 'update_site_option_active_sitewide_plugins', 'bb_flush_feature_caches_on_plugin_change' );

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

	// Whitelist only the keys expected by the Pro sanitizer so a malicious
	// client cannot persist arbitrary nested data into the option — even
	// though the option is inert without Pro, we avoid storing junk.
	$allowed_keys = array( 'type', 'sub_type', 'allowed_roles', 'options' );

	$sanitized = array();
	foreach ( $allowed_keys as $key ) {
		if ( ! array_key_exists( $key, $value ) ) {
			continue;
		}

		$entry = $value[ $key ];

		if ( is_array( $entry ) ) {
			$sanitized[ $key ] = map_deep( $entry, 'sanitize_text_field' );
		} elseif ( is_scalar( $entry ) ) {
			$sanitized[ $key ] = sanitize_text_field( (string) $entry );
		}
		// Non-array, non-scalar entries are dropped.
	}

	return $sanitized;
}
