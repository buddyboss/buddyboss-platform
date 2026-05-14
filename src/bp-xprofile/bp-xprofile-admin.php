<?php
/**
 * BuddyPress XProfile Admin.
 *
 * Legacy admin UI (field list, add/edit/delete screens, AJAX reorder,
 * admin tabs) removed — now managed via Settings 2.0.
 *
 * Only runtime hooks remain: repeater-field migration helpers that fire
 * on `xprofile_group_before_save` / `xprofile_group_after_save`.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 *
 * @since BuddyPress 1.0.0
 *
 * @return bool
 */
function xprofile_add_admin_menu() {
	global $submenu;

	// Bail if current user cannot moderate community.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Register the menu item pointing directly to the React Settings 2.0 page.
	// Direct visits to the legacy `?page=bp-profile-setup` are caught upstream
	// by `bb_redirect_bp_settings_before_permission_check()` at
	// `admin_menu @ PHP_INT_MAX` so they don't 403.
	$settings_url = function_exists( 'bb_get_feature_settings_url' )
		? bb_get_feature_settings_url( 'members', 'profile_fields' )
		: admin_url( 'admin.php?page=bb-settings&tab=members&panel=profile_fields' );

	$submenu['buddyboss-platform'][] = array(
		__( 'Profiles', 'buddyboss' ),
		'bp_moderate',
		$settings_url,
	);
}
add_action( bp_core_admin_hook(), 'xprofile_add_admin_menu' );

/**
 * Save repeater option temporary before save group details.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param object|BP_XProfile_Group $xprofile Current instance of the group being saved.
 */
function bb_xprofile_before_save_xprofile_group_details( $xprofile ) {
	// Save the previous data to use later.
	$is_repeater_enabled = BP_XProfile_Group::get_group_meta( $xprofile->id, 'is_repeater_enabled' );
	bp_update_option( 'xprofile_group_' . $xprofile->id, $is_repeater_enabled );
}
add_action( 'xprofile_group_before_save', 'bb_xprofile_before_save_xprofile_group_details', 11, 1 );

/**
 * Migrating the user simple data's to repeater fields data's.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param object|BP_XProfile_Group $xprofile Current instance of the group being saved.
 */
function bb_xprofile_migrate_simple_to_repeater_fields_data( $xprofile ) {
	global $wpdb, $bp, $bb_background_updater;

	$repeater_enabled = isset( $_POST['group_is_repeater'] ) && 'on' == $_POST['group_is_repeater'] ? 'on' : 'off';
	$previous_value   = bp_get_option( 'xprofile_group_' . $xprofile->id );

	if (
		'on' === $repeater_enabled &&
		'on' !== $previous_value &&
		! empty( $xprofile->id )
	) {
		$repeater_template_fields = bp_get_repeater_template_field_ids( $xprofile->id );

		// Check if clone fields not created then create it.
		$repeater_fields = bp_get_repeater_clone_field_ids_all( $xprofile->id );
		if ( empty( $repeater_fields ) ) {
			$user_field_set_count = bp_get_profile_field_set_count( $xprofile->id, get_current_user_id() );
			bp_get_repeater_clone_field_ids_subset( $xprofile->id, $user_field_set_count );
		}

		if ( ! empty( $repeater_template_fields ) ) {
			$repeater_template_fields_in = "'" . implode( "','", $repeater_template_fields ) . "'";

			$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$bp->profile->table_name_data} WHERE field_id IN ({$repeater_template_fields_in})" );

			if ( ! empty( $user_ids ) ) {

				$min_count = (int) apply_filters( 'bb_xprofile_migrate_repeater_queue_min_count', 10 );
				if ( $min_count && count( $user_ids ) > $min_count ) {

					$chunk_user_ids = array_chunk( $user_ids, $min_count );
					if ( ! empty( $chunk_user_ids ) ) {
						foreach ( $chunk_user_ids as $chunked_user_ids ) {
							$bb_background_updater->data(
								array(
									'type'     => 'xprofile',
									'group'    => 'xprofile_simple_field_to_repeater',
									'priority' => 5,
									'callback' => 'bb_xprofile_mapping_simple_to_repeater_fields_data',
									'args'     => array( $chunked_user_ids, $xprofile->id ),
								)
							);
							$bb_background_updater->save();
						}
					}

					$bb_background_updater->dispatch();
				} else {
					bb_xprofile_mapping_simple_to_repeater_fields_data( $user_ids, $xprofile->id );
				}
			}
		}
	}

	// Delete the option.
	bp_delete_option( 'xprofile_group_' . $xprofile->id );
}
add_action( 'xprofile_group_after_save', 'bb_xprofile_migrate_simple_to_repeater_fields_data', 11, 1 );

/**
 * Prepare data to insert into repeater fields.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param array $user_ids Array of user ID's.
 * @param int   $group_id Xprofile group ID.
 */
function bb_xprofile_mapping_simple_to_repeater_fields_data( $user_ids, $group_id ) {
	if ( ! empty( $user_ids ) ) {
		foreach ( $user_ids as $user_id ) {
			$clone_fields = bp_get_repeater_clone_field_ids_subset( $group_id, 1 );
			if ( ! empty( $clone_fields ) ) {
				foreach ( $clone_fields as $clone_field_id ) {
					$data              = xprofile_get_field_data( $clone_field_id, $user_id );
					$template_field_id = bp_xprofile_get_meta( $clone_field_id, 'field', '_cloned_from', true );
					if ( empty( $data ) && ! empty( $template_field_id ) && null !== bb_xprofile_get_field_type( $template_field_id ) ) {
						xprofile_set_field_data( $clone_field_id, $user_id, xprofile_get_field_data( $template_field_id, $user_id ) );
					}
				}
			}
		}
	}
}
