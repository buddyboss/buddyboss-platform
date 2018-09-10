<?php
/**
 * BP Follow Functions
 *
 * @package BP-Follow
 * @subpackage Functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Start following a user's activity.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to follow.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function bp_follow_start_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'   => bp_displayed_user_id(),
		'follower_id' => bp_loggedin_user_id()
	) );

	$follow = new BP_Friends_Follow( $r['leader_id'], $r['follower_id'] );

	// existing follow already exists
	if ( ! empty( $follow->id ) ) {
		return false;
	}

	if ( ! $follow->save() ) {
		return false;
	}

	do_action_ref_array( 'bp_follow_start_following', array( &$follow ) );

	return true;
}

/**
 * Stop following a user's activity.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to stop following.
 *     @type int $follower_id The user ID initiating the unfollow request.
 * }
 * @return bool
 */
function bp_follow_stop_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'   => bp_displayed_user_id(),
		'follower_id' => bp_loggedin_user_id()
	) );

	$follow = new BP_Friends_Follow( $r['leader_id'], $r['follower_id'] );

	if ( empty( $follow->id ) || ! $follow->delete() ) {
		return false;
	}

	do_action_ref_array( 'bp_follow_stop_following', array( &$follow ) );

	return true;
}

/**
 * Check if a user is already following another user.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to check.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function bp_follow_is_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'   => bp_displayed_user_id(),
		'follower_id' => bp_loggedin_user_id()
	) );

	$follow = new BP_Friends_Follow( $r['leader_id'], $r['follower_id'] );

	return apply_filters( 'bp_follow_is_following', (int)$follow->id, $follow );
}

/**
 * Fetch the user IDs of all the followers of a particular user.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to get followers for.
 * }
 * @return array
 */
function bp_follow_get_followers( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_displayed_user_id()
	) );

	return apply_filters( 'bp_follow_get_followers', BP_Friends_Follow::get_followers( $r['user_id'] ) );
}

/**
 * Fetch the user IDs of all the users a particular user is following.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to fetch following user IDs for.
 * }
 * @return array
 */
function bp_follow_get_following( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_displayed_user_id()
	) );

	return apply_filters( 'bp_follow_get_following', BP_Friends_Follow::get_following( $r['user_id'] ) );
}

/**
 * Get the total followers and total following counts for a user.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to grab follow counts for.
 * }
 * @return array [ followers => int, following => int ]
 */
function bp_follow_total_follow_counts( $args = '' ) {

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
		$count = BP_Friends_Follow::get_counts( $r['user_id'] );
	}

	return apply_filters( 'bp_follow_total_follow_counts', $count, $r['user_id'] );
}

/**
 * Removes follow relationships for all users from a user who is deleted or spammed
 *
 * @since 1.0.0
 *
 * @uses BP_Friends_Follow::delete_all_for_user() Deletes user ID from all following / follower records
 */
function bp_follow_remove_data( $user_id ) {
	do_action( 'bp_follow_before_remove_data', $user_id );

	BP_Friends_Follow::delete_all_for_user( $user_id );

	do_action( 'bp_follow_remove_data', $user_id );
}
add_action( 'wpmu_delete_user',	'bp_follow_remove_data' );
add_action( 'delete_user',	'bp_follow_remove_data' );
add_action( 'make_spam_user',	'bp_follow_remove_data' );

/**
 * Auto follow users when they connect with each other
 *
 * @since 3.1.1
 *
 * @uses bp_follow_start_following()
 */
function bp_follow_auto_follow_users( $friendship_id, $initiator_user_id, $friend_user_id ) {

	// initiator user follows friend
	bp_follow_start_following( array(
		'leader_id'   => $initiator_user_id,
		'follower_id' => $friend_user_id
	) );

	// friend user follows initiator
	bp_follow_start_following( array(
		'leader_id'   => $friend_user_id,
		'follower_id' => $initiator_user_id
	) );

}
add_action( 'friends_friendship_accepted', 'bp_follow_auto_follow_users', 10, 3 );

/**
 * Auto unfollow users when they connect with each other
 *
 * @since 3.1.1
 *
 * @uses bp_follow_stop_following()
 */
function bp_follow_auto_unfollow_users( $friendship_id, $initiator_user_id, $friend_user_id ) {

	// initiator user unfollows friend
	bp_follow_stop_following( array(
		'leader_id'   => $initiator_user_id,
		'follower_id' => $friend_user_id
	) );

	// friend user unfollows initiator
	bp_follow_stop_following( array(
		'leader_id'   => $friend_user_id,
		'follower_id' => $initiator_user_id
	) );

}
add_action( 'friends_friendship_deleted', 'bp_follow_auto_unfollow_users', 10, 3 );

/**
 * Exclude unfollowed user's feeds
 *
 * @since 3.1.1
 */
function bp_follow_exclude_unfollow_feed( $args ) {

	if ( bp_loggedin_user_id() ) {
		$friends = friends_get_friend_user_ids( bp_loggedin_user_id() );

		if ( ! empty( $friends ) ) {
			$unfollowing = array();

			foreach ( $friends as $friend ) {
				if ( ! bp_follow_is_following( array(
					'leader_id'   => $friend,
					'follower_id' => bp_loggedin_user_id()
				) ) ) {
					array_push( $unfollowing, $friend );
				}
			}

			if ( ! empty( $unfollowing ) ) {

				$filter_query = array(
					array(
						'column'  => 'user_id',
						'value'   => $unfollowing,
						'compare' => 'NOT IN',
					)
				);

				// Are mentions disabled?
				if ( bp_activity_do_mentions() ) {
					$filter_query['relation'] = 'OR';
					$filter_query[] = array(
						array(
							'column'  => 'content',
							'compare' => 'LIKE',

							// Start search at @ symbol and stop search at closing tag delimiter.
							'value'   => '@' . bp_activity_get_user_mentionname( bp_loggedin_user_id() ) . '<'
						),
					);
				}

				if ( ! empty( $args['filter_query'] ) ) {
					array_push( $args['filter_query'], $filter_query );
				} else {
					$args['filter_query'] = array(
						$filter_query
					);
				}
			}
		}
	}

	return $args;
}

add_filter( 'bp_after_has_activities_parse_args', 'bp_follow_exclude_unfollow_feed' );
