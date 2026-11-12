<?php
/**
 * BuddyPress profile search functions.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_include', 'bboss_core_load_profile_search', 11 );

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
		add_action( 'bp_include', 'bboss_bps_buddypress' );
		function bboss_bps_buddypress() {
		}
	}

	define( 'BBOSS_BPS_VERSION', BBOSS_PLATFORM_VERSION );

	if ( bboss_is_active( 'xprofile' ) ) {
		include bboss()->plugin_dir . 'bboss-core/profile-search/bps-start.php';
	}
}
