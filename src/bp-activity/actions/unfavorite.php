<?php
/**
 * Activity: Remove like
 *
 * @package BuddyBoss\Activity\Actions\Like
 * @since BuddyPress 3.0.0
 */

/**
 * Remove activity from likes.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 * @todo is this still used?
 */
function bp_activity_action_remove_favorite() {
	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'unfavorite' ) ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'unmark_favorite' );

	if ( bp_activity_remove_user_favorite( bp_action_variable( 0 ) ) ) {
		bp_core_add_message( __( 'Post unsaved.', 'buddyboss' ) );
	} else {
		bp_core_add_message( __( 'There was an error unsaving that post. Please try again.', 'buddyboss' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
}
add_action( 'bp_actions', 'bp_activity_action_remove_favorite' );
