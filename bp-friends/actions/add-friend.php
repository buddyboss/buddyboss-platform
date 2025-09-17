<?php
/**
 * Connections: Add action
 *
 * @package BuddyBoss\Connections\Actions
 * @since BuddyPress 3.0.0
 */

/**
 * Catch and process connection requests.
 *
 * @since BuddyPress 1.0.1
 */
function friends_action_add_friend() {
	if ( ! bp_is_friends_component() || ! bp_is_current_action( 'add-friend' ) ) {
		return false;
	}

	if ( ! $potential_friend_id = (int) bp_action_variable( 0 ) ) {
		return false;
	}

	if ( $potential_friend_id == bp_loggedin_user_id() ) {
		return false;
	}

	$friendship_status = BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $potential_friend_id );

	if ( 'not_friends' == $friendship_status ) {

		if ( ! check_admin_referer( 'friends_add_friend' ) ) {
			return false;
		}

		if ( ! friends_add_friend( bp_loggedin_user_id(), $potential_friend_id ) ) {
			bp_core_add_message( __( 'Connection could not be requested.', 'buddyboss' ), 'error' );
		} else {
			bp_core_add_message( __( 'Connection requested', 'buddyboss' ) );
		}
	} elseif ( 'is_friend' == $friendship_status ) {
		bp_core_add_message( __( 'You are already connected with this user', 'buddyboss' ), 'error' );
	} else {
		bp_core_add_message( __( 'You already have a pending connection request with this user', 'buddyboss' ), 'error' );
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'bp_actions', 'friends_action_add_friend' );
