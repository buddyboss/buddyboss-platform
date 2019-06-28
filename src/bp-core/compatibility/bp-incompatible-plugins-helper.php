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
function bp_core_update_group_fields_id_in_db( $bypass = false ) {

	if ( is_multisite() ) {
		global $wpdb;
		$bp_prefix = bp_core_get_table_prefix();

		$table_name = $bp_prefix . 'bp_xprofile_fields';

		if ( empty( bp_xprofile_firstname_field_id( 1, false ) ) ) {
			//first name fields update
			$firstname = bp_get_option( 'bp-xprofile-firstname-field-name' );
			$results   = $wpdb->get_results( "SELECT id FROM {$table_name} WHERE name = '{$firstname}'" );
			$count     = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-firstname-field-id', $id );
						$count ++;
					} else {
						$wpdb->update( $table_name, array( 'can_delete' => 1 ), array( 'id' => $id ) );
					}
				}
			}
		}


		if ( empty( bp_xprofile_lastname_field_id( 0, false ) ) ) {
			//last name fields update
			$lastname = bp_get_option( 'bp-xprofile-lastname-field-name' );
			$results  = $wpdb->get_results( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$lastname}'" );
			$count    = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-lastname-field-id', $id );
						$count ++;
					} else {
						$wpdb->update( $table_name, array( 'can_delete' => 1 ), array( 'id' => $id ) );
					}
				}
			}
		}

		if ( empty( bp_xprofile_nickname_field_id( 0, false ) ) ) {
			//nick name fields update
			$nickname = bp_get_option( 'bp-xprofile-nickname-field-name' );
			$results  = $wpdb->get_results( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$nickname}'" );
			$count    = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-nickname-field-id', $id );
						$count ++;
					} else {
						$wpdb->update( $table_name, array( 'can_delete' => 1 ), array( 'id' => $id ) );
					}
				}
			}
		}

		add_site_option( 'bp-xprofile-field-ids-updated', 1 );
	}
}

add_action( 'xprofile_admin_group_action', 'bp_core_update_group_fields_id_in_db', 100 );