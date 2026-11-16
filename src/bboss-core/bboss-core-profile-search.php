<?php
/**
 * BuddyPress profile search functions.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases-pre — pluggable functions (decision 0013): a site that defined the
// pre-rename name first keeps its override, and Platform's guarded declaration below stands down.
if ( function_exists( 'bps_buddypress' ) && ! function_exists( 'bboss_bps_buddypress' ) ) {
	// Pluggable: bps_buddypress() was defined first (bp-custom.php, a plugin); bboss_bps_buddypress() delegates to it.
	function bboss_bps_buddypress() {
		return bps_buddypress();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_include', 'bp_core_load_profile_search', 11 );

/**
 * Load main profile search module.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_core_load_profile_search() {
	if ( defined( 'BBOSS_BPS_VERSION' ) || function_exists( 'bboss_bps_buddypress' ) ) {
		return false;// do not load !
	}

	if ( bboss_disable_advanced_profile_search() ) {
		return false;// do not load
	}

	// Added this action and function because of Geo my WP plugin checking if this function exists.
	if ( ! function_exists( 'bboss_bps_buddypress' ) && in_array( 'geo-my-wp/geo-my-wp.php', apply_filters( 'active_plugins', (array) get_option( 'active_plugins', array() ) ), true ) ) {
		add_action( 'bp_include', 'bps_buddypress' );
		function bboss_bps_buddypress() {
		}
		// @bbwt-com-start compat-layer
		// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
		if ( function_exists( 'bboss_bps_buddypress' ) && ! function_exists( 'bps_buddypress' ) ) {
			/**
			 * Backward-compatible alias of {@see bboss_bps_buddypress()}.
			 *
			 * @deprecated Use bboss_bps_buddypress() instead.
			 */
			function bps_buddypress() {
				return bboss_bps_buddypress();
			}
		}
		// @bbwt-com-end compat-layer

	}

	define( 'BBOSS_BPS_VERSION', BBOSS_PLATFORM_VERSION );

	if ( bboss_is_active( 'xprofile' ) ) {
		include bboss()->plugin_dir . 'bboss-core/profile-search/bps-start.php';
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_load_profile_search' ) && ! function_exists( 'bp_core_load_profile_search' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_load_profile_search()}.
	 *
	 * @deprecated Use bboss_core_load_profile_search() instead.
	 */
	function bp_core_load_profile_search() {
		return bboss_core_load_profile_search();
	}
}
// @bbwt-com-end compat-layer

