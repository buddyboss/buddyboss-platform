<?php
/**
 * BuddyBoss Follow Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss
 * @subpackage FollowFunctions
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Start following a user's activity.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to stop following.
 *     @type int $follower_id The user ID initiating the unfollow request.
 * }
 * @return bool
 */
function bp_start_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'   => bp_displayed_user_id(),
		'follower_id' => bp_loggedin_user_id()
	) );

	$follow = new BP_Follow( $r['leader_id'], $r['follower_id'] );

	// existing follow already exists
	if ( ! empty( $follow->id ) ) {
		return false;
	}

	if ( ! $follow->save() ) {
		return false;
	}

	do_action_ref_array( 'bp_start_following', array( &$follow ) );

	return true;
}

/**
 * Stop following a user's activity.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to follow.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function bp_stop_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'   => bp_displayed_user_id(),
		'follower_id' => bp_loggedin_user_id()
	) );

	$follow = new BP_Follow( $r['leader_id'], $r['follower_id'] );

	if ( empty( $follow->id ) || ! $follow->delete() ) {
		return false;
	}

	do_action_ref_array( 'bp_stop_following', array( &$follow ) );

	return true;
}

/**
 * Check if a user is already following another user.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to check.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function bp_is_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'   => bp_displayed_user_id(),
		'follower_id' => bp_loggedin_user_id()
	) );

	$follow = new BP_Follow( $r['leader_id'], $r['follower_id'] );

	return apply_filters( 'bp_is_following', (int)$follow->id, $follow );
}

/**
 * Fetch the user IDs of all the followers of a particular user.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to get followers for.
 * }
 * @return array
 */
function bp_get_followers( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_displayed_user_id()
	) );

	return apply_filters( 'bp_get_followers', BP_Follow::get_followers( $r['user_id'] ) );
}

/**
 * Fetch the user IDs of all the users a particular user is following.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to fetch following user IDs for.
 * }
 * @return array
 */
function bp_get_unfollowing( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_displayed_user_id()
	) );

	return apply_filters( 'bp_get_unfollowing', BP_Follow::get_following( $r['user_id'] ) );
}

/**
 * Get the total followers and total following counts for a user.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to grab follow counts for.
 * }
 * @return array [ followers => int, following => int ]
 */
function bp_total_follow_counts( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_loggedin_user_id()
	) );

	$count = false;

	/* try to get locally-cached values first */

	// logged-in user
	if ( $r['user_id'] == bp_loggedin_user_id() && is_user_logged_in() ) {
		global $bp;

		if ( ! empty( $bp->loggedin_user->total_follow_counts ) ) {
			$count = $bp->loggedin_user->total_follow_counts;
		}

		// displayed user
	} elseif ( $r['user_id'] == bp_displayed_user_id() && bp_is_user() ) {
		global $bp;

		if ( ! empty( $bp->displayed_user->total_follow_counts ) ) {
			$count = $bp->displayed_user->total_follow_counts;
		}
	}

	// no cached value, so query for it
	if ( $count === false ) {
		$count = BP_Follow::get_counts( $r['user_id'] );
	}

	return apply_filters( 'bp_total_follow_counts', $count, $r['user_id'] );
}

/**
 * Removes follow relationships for all users from a user who is deleted or spammed
 *
 * @since BuddyBoss 3.1.1
 *
 * @uses BP_Follow::delete_all_for_user() Deletes user ID from all following / follower records
 */
function bp_remove_follow_data( $user_id ) {
	do_action( 'bp_before_remove_follow_data', $user_id );

	BP_Follow::delete_all_for_user( $user_id );

	do_action( 'bp_remove_follow_data', $user_id );
}
add_action( 'wpmu_delete_user',	'bp_remove_follow_data' );
add_action( 'delete_user',	'bp_remove_follow_data' );
add_action( 'make_spam_user',	'bp_remove_follow_data' );

/**
 * Auto follow users when they connect with each other
 *
 * @since BuddyBoss 3.1.1
 *
 * @uses bp_start_following()
 */
function bp_auto_follow_users( $friendship_id, $initiator_user_id, $friend_user_id ) {

	// initiator user follows friend
	bp_start_following( array(
		'leader_id'   => $initiator_user_id,
		'follower_id' => $friend_user_id
	) );

	// friend user follows initiator
	bp_start_following( array(
		'leader_id'   => $friend_user_id,
		'follower_id' => $initiator_user_id
	) );

}
add_action( 'friends_friendship_accepted', 'bp_auto_follow_users', 10, 3 );

/**
 * Auto follow users when they connect with each other
 *
 * @since BuddyBoss 3.1.1
 *
 * @uses bp_stop_following()
 */
function bp_auto_unfollow_users( $friendship_id, $initiator_user_id, $friend_user_id ) {

	// initiator user unfollows friend
	bp_stop_following( array(
		'leader_id'   => $initiator_user_id,
		'follower_id' => $friend_user_id
	) );

	// friend user unfollows initiator
	bp_stop_following( array(
		'leader_id'   => $friend_user_id,
		'follower_id' => $initiator_user_id
	) );

}
add_action( 'friends_friendship_deleted', 'bp_auto_unfollow_users', 10, 3 );

/**
 * Exclude unfollowed user's feeds
 *
 * @since BuddyBoss 3.1.1
 */
function bp_exclude_unfollow_feed( $args ) {

	if ( bp_loggedin_user_id() && bp_is_activity_directory() ) {
		$args['scope'] = 'friends,mentions';
	}

	return $args;
}
//add_filter( 'bp_after_has_activities_parse_args', 'bp_exclude_unfollow_feed' );
