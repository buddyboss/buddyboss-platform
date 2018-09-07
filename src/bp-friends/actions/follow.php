<?php
/**
 * Connections: follow action
 *
 * @package BuddyBoss
 * @subpackage FollowActions
 * @since BuddyPress 3.1.1
 */

/**
 * Catch and process follow requests.
 *
 * @since BuddyPress 3.1.1
 */
function friends_action_follow() {
	if ( !bp_is_friends_component() || !bp_is_current_action( 'follow' ) )
		return false;

	if ( !$leader_id = (int)bp_action_variable( 0 ) )
		return false;

	if ( $leader_id == bp_loggedin_user_id() )
		return false;

	if ( ! friends_check_friendship( bp_loggedin_user_id(), $leader_id ) ) {
		bp_core_add_message( __( 'You are not connected with this user.', 'buddyboss' ) );
		return false;
	}

	$follow_status = bp_follow_is_following( array( 'leader_id' => $leader_id, 'follower_id' => bp_loggedin_user_id() ) );

	if ( $follow_status ) {

		bp_core_add_message( __( 'You are already following this user.', 'buddyboss' ), 'error' );

	} else {
		if ( ! bp_follow_start_following( array( 'leader_id' => $leader_id, 'follower_id' => bp_loggedin_user_id() ) ) ) {
			bp_core_add_message( __( 'There was a problem when trying to follow this user, please try again.', 'buddyboss' ), 'error' );
		} else {
			bp_core_add_message( __( 'You are now following this user.', 'buddyboss' ) );
		}
	}

	bp_core_redirect( wp_get_referer() );

	return false;
}
add_action( 'bp_actions', 'friends_action_follow' );