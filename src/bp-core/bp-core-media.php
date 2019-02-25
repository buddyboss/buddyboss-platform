<?php
/**
 * BuddyPress Core Media
 * 
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_include', 'bp_core_load_media', 11 );

/**
 * Load main media module.
 * 
 * @since BuddyBoss 1.0.0
 */
function bp_core_load_media() {
    if ( defined( 'BUDDYBOSS_MEDIA_PLUGIN_VERSION' ) || function_exists( 'buddyboss_media_init' ) ) {
        return false;//do not load !
    }

//    if ( bp_disable_advanced_profile_search() ) {
//        return false;//do not load
//    }
    
    //define ( 'BPS_VERSION', BP_VERSION );

	include buddypress()->plugin_dir . 'bp-core/media/buddyboss-media.php';
}