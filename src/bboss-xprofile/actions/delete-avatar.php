<?php
/**
 * XProfile: Avatar deletion action handler
 *
 * @package BuddyBoss\XProfile\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Deletes the active avatar for a user.
 *
 * This function runs when an action is set for a screen:
 * example.com/members/andy/profile/change-avatar/ [delete-avatar]
 *
 * @since BuddyPress 1.0.0
 */
function bboss_xprofile_action_delete_avatar() {

	if ( ! bboss_is_user_change_avatar() || ! bboss_is_action_variable( 'delete-avatar', 0 ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'bp_delete_avatar_link' );

	if ( ! bboss_is_my_profile() && ! bboss_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	if ( bboss_core_delete_existing_avatar( array( 'item_id' => bboss_displayed_user_id() ) ) ) {
		bboss_core_add_message( __( 'Your profile photo was deleted successfully!', 'buddyboss' ) );
	} else {
		bboss_core_add_message( __( 'There was a problem deleting your profile photo. Please try again.', 'buddyboss' ), 'error' );
	}

	bboss_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bboss_xprofile_action_delete_avatar' );
