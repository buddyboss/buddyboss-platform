<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


/**
 * Remove BuddyPress Follow init hook action
 *
 * Support BuddyPress Follow
 */
remove_action( 'bp_include', 'bp_follow_init' );

/**
 * Remove message of BuddyPress Groups Export & Import
 *
 * Support BuddyPress Groups Export & Import
 */
remove_action( 'plugins_loaded', 'bpgei_plugin_init' );

/**
 * Include plugin when plugin is activated
 *
 * Support Rank Math SEO
 */
function bp_helper_plugins_loaded_callback() {
	global $bp_plugins;
	if ( in_array( 'seo-by-rank-math/rank-math.php', $bp_plugins ) && ! is_admin() ) {
		require( buddypress()->plugin_dir . '/bp-core/compatibility/bp-rankmath-plugin-helpers.php' );
	}
}

add_action( 'init', 'bp_helper_plugins_loaded_callback', 1000 );