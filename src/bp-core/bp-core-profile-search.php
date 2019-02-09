<?php
/**
 * BuddyPress profile search functions.
 * 
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_include', 'bp_core_load_profile_search', 11 );

/**
 * Load main profile search module.
 * 
 * @since BuddyBoss 1.0.0
 */
function bp_core_load_profile_search () {
    if ( defined( 'BPS_VERSION' ) || function_exists( 'bps_buddypress' ) ) {
        return false;//do not load !
    }
    
    if ( bp_disable_advanced_profile_search() ) {
        return false;//do not load
    }
    
    define ( 'BPS_VERSION', BP_VERSION );
    
    if ( bp_is_active ( 'xprofile' ) ) {
		include buddypress()->plugin_dir . 'bp-core/profile-search/bps-start.php';
	}
}