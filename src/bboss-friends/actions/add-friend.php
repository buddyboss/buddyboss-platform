<?php
/**
 * Connections: Add action
 *
 * @package BuddyBoss\Connections\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process connection requests.
 *
 * @since BuddyPress 1.0.1
 */
function bboss_friends_action_add_friend() {
	if ( ! bboss_is_friends_component() || ! bboss_is_current_action( 'add-friend' ) ) {
		return false;
	}

	if ( ! $potential_friend_id = (int) bboss_action_variable( 0 ) ) {
		return false;
	}

	if ( $potential_friend_id == bboss_loggedin_user_id() ) {
		return false;
	}

	$friendship_status = BBoss_Friends_Friendship::check_is_friend( bboss_loggedin_user_id(), $potential_friend_id );

	if ( 'not_friends' == $friendship_status ) {

		if ( ! check_admin_referer( 'friends_add_friend' ) ) {
			return false;
		}

		if ( ! bboss_friends_add_friend( bboss_loggedin_user_id(), $potential_friend_id ) ) {
			bboss_core_add_message( __( 'Connection could not be requested.', 'buddyboss' ), 'error' );
		} else {
			bboss_core_add_message( __( 'Connection requested', 'buddyboss' ) );
		}
	} elseif ( 'is_friend' == $friendship_status ) {
		bboss_core_add_message( __( 'You are already connected with this user', 'buddyboss' ), 'error' );
	} else {
		bboss_core_add_message( __( 'You already have a pending connection request with this user', 'buddyboss' ), 'error' );
	}

	bboss_core_redirect( wp_get_referer() );

	return false;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_friends_action_add_friend' ) && ! function_exists( 'friends_action_add_friend' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_friends_action_add_friend()}.
	 *
	 * @deprecated Use bboss_friends_action_add_friend() instead.
	 */
	function friends_action_add_friend() {
		return bboss_friends_action_add_friend();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_actions', 'friends_action_add_friend' );
