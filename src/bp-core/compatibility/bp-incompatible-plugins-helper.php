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

/**
 * On BuddyPress update
 *
 * @since BuddyBoss 1.0.9
 */
function bp_core_update_group_fields_id_in_db() {

	if ( is_multisite()) {
		global $wpdb;
		$bp_prefix = bp_core_get_table_prefix();

		if ( empty( bp_xprofile_firstname_field_id( 1, false ) ) ) {
			//first name fields update
			$firstname = bp_get_option( 'bp-xprofile-firstname-field-name' );
			$result    = $wpdb->get_row( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$firstname}'", ARRAY_A );
			if ( ! empty( $result['id'] ) ) {
				add_site_option( 'bp-xprofile-firstname-field-id', $result['id'] );
			}
		}

		if ( empty( bp_xprofile_lastname_field_id( 0, false ) ) ) {
			//last name fields update
			$lastname = bp_get_option( 'bp-xprofile-lastname-field-name' );
			$result   = $wpdb->get_row( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$lastname}'", ARRAY_A );
			if ( ! empty( $result['id'] ) ) {
				add_site_option( 'bp-xprofile-lastname-field-id', $result['id'] );
			}
		}

		if ( empty( bp_xprofile_nickname_field_id( 0, false ) ) ) {
			//nick name fields update
			$nickname = bp_get_option( 'bp-xprofile-nickname-field-name' );
			$result   = $wpdb->get_row( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$nickname}'", ARRAY_A );
			if ( ! empty( $result['id'] ) ) {
				add_site_option( 'bp-xprofile-nickname-field-id', $result['id'] );
			}
		}
	}
}