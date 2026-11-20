<?php
/**
 * BuddyBoss Connections Caching.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyBoss\Connections\Caching
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Clear friends-related cache for members of a specific friendship.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $friendship_id ID of the friendship whose two members should
 *                           have their friends cache busted.
 * @return bool
 */
function friends_clear_friend_object_cache( $friendship_id ) {
	if ( ! $friendship = new BP_Friends_Friendship( $friendship_id ) ) {
		return false;
	}

	wp_cache_delete( 'friends_friend_ids_' . $friendship->initiator_user_id, 'bp' );
	wp_cache_delete( 'friends_friend_ids_' . $friendship->friend_user_id, 'bp' );
	wp_cache_delete( 'bp_friends_user_query_' . $friendship->initiator_user_id, 'bp' );
	wp_cache_delete( 'bp_friends_user_query_' . $friendship->friend_user_id, 'bp' );
}

// List actions to clear object caches on.
add_action( 'friends_friendship_accepted', 'friends_clear_friend_object_cache' );
add_action( 'friends_friendship_deleted', 'friends_clear_friend_object_cache' );

/**
 * Clear friendship caches on friendship changes.
 *
 * @since BuddyPress 2.7.0
 *
 * @param int $friendship_id     ID of the friendship that has changed.
 * @param int $initiator_user_id ID of the first user.
 * @param int $friend_user_id    ID of the second user.
 * @return bool
 */
function bp_friends_clear_bp_friends_friendships_cache( $friendship_id, $initiator_user_id, $friend_user_id ) {
	// Clear friendship ID cache for each user.
	wp_cache_delete( $initiator_user_id, 'bp_friends_friendships_for_user' );
	wp_cache_delete( $friend_user_id, 'bp_friends_friendships_for_user' );

	// Clear the friendship object cache.
	wp_cache_delete( $friendship_id, 'bp_friends_friendships' );

	// Clear incremented cache.
	$friendship                    = new stdClass();
	$friendship->initiator_user_id = $initiator_user_id;
	$friendship->friend_user_id    = $friend_user_id;
	bp_friends_delete_cached_friendships_on_friendship_save( $friendship );

	wp_cache_delete( 'get_friendship_ids_for_user_' . $initiator_user_id, 'bp_friends_friendships_for_user' );
	wp_cache_delete( 'get_friendship_ids_for_user_' . $friend_user_id, 'bp_friends_friendships_for_user' );
}
add_action( 'friends_friendship_requested', 'bp_friends_clear_bp_friends_friendships_cache', 10, 3 );
add_action( 'friends_friendship_accepted', 'bp_friends_clear_bp_friends_friendships_cache', 10, 3 );
add_action( 'friends_friendship_deleted', 'bp_friends_clear_bp_friends_friendships_cache', 10, 3 );

/**
 * Clear friendship caches on friendship changes.
 *
 * @since BuddyPress 2.7.0
 *
 * @param int                   $friendship_id The friendship ID.
 * @param BP_Friends_Friendship $friendship Friendship object.
 */
function bp_friends_clear_bp_friends_friendships_cache_remove( $friendship_id, BP_Friends_Friendship $friendship ) {
	// Clear friendship ID cache for each user.
	wp_cache_delete( $friendship->initiator_user_id, 'bp_friends_friendships_for_user' );
	wp_cache_delete( $friendship->friend_user_id, 'bp_friends_friendships_for_user' );

	// Clear the friendship object cache.
	wp_cache_delete( $friendship_id, 'bp_friends_friendships' );

	// Clear incremented cache.
	bp_friends_delete_cached_friendships_on_friendship_save( $friendship );

	wp_cache_delete( 'get_friendship_ids_for_user_' . $friendship->initiator_user_id, 'bp_friends_friendships_for_user' );
	wp_cache_delete( 'get_friendship_ids_for_user_' . $friendship->friend_user_id, 'bp_friends_friendships_for_user' );
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_clear_bp_friends_friendships_cache_remove', 10, 2 );
add_action( 'friends_friendship_rejected', 'bp_friends_clear_bp_friends_friendships_cache_remove', 10, 2 );

/**
 * Clear the friend request cache for the user not initiating the friendship.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $friend_user_id The user ID not initiating the friendship.
 */
function bp_friends_clear_request_cache( $friend_user_id ) {
	wp_cache_delete( $friend_user_id, 'bp_friends_requests' );
}

/**
 * Clear the friend request cache when a friendship is saved.
 *
 * A friendship is deemed saved when a friendship is requested or accepted.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $friendship_id     The friendship ID.
 * @param int $initiator_user_id The user ID initiating the friendship.
 * @param int $friend_user_id    The user ID not initiating the friendship.
 */
function bp_friends_clear_request_cache_on_save( $friendship_id, $initiator_user_id, $friend_user_id ) {
	bp_friends_clear_request_cache( $friend_user_id );
}
add_action( 'friends_friendship_requested', 'bp_friends_clear_request_cache_on_save', 10, 3 );
add_action( 'friends_friendship_accepted', 'bp_friends_clear_request_cache_on_save', 10, 3 );

/**
 * Clear the friend request cache when a friendship is removed.
 *
 * A friendship is deemed removed when a friendship is withdrawn or rejected.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int                   $friendship_id The friendship ID.
 * @param BP_Friends_Friendship $friendship Friendship object.
 */
function bp_friends_clear_request_cache_on_remove( $friendship_id, BP_Friends_Friendship $friendship ) {
	bp_friends_clear_request_cache( $friendship->friend_user_id );
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_clear_request_cache_on_remove', 10, 2 );
add_action( 'friends_friendship_rejected', 'bp_friends_clear_request_cache_on_remove', 10, 2 );

/**
 * Delete individual friendships from the cache when they are changed.
 *
 * @since BuddyPress 3.0.0
 *
 * @param BP_Friends_Friendship $friendship Friendship object.
 */
function bp_friends_delete_cached_friendships_on_friendship_save( $friendship ) {
	bp_core_delete_incremented_cache( $friendship->friend_user_id . ':' . $friendship->initiator_user_id, 'bp_friends' );
	bp_core_delete_incremented_cache( $friendship->initiator_user_id . ':' . $friendship->friend_user_id, 'bp_friends' );
}
add_action( 'friends_friendship_after_save', 'bp_friends_delete_cached_friendships_on_friendship_save' );

/**
 * Store, or pull, the members related to a user whose connection data is being removed.
 *
 * `BP_Friends_Friendship::delete_all_for_user()` deletes friendships and friend
 * notifications with direct queries, so the other members involved must be
 * captured before the rows are gone and their caches cleared afterwards.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int        $user_id ID of the user whose connection data is being removed.
 * @param array|null $related Optional. Related member data to store. Omit to pull
 *                            (return and forget) the stored data.
 *
 * @return array {
 *     Related member data.
 *
 *     @type int[]    $user_ids      IDs of the members with a friendship or request
 *                                   of any status with the user.
 *     @type object[] $notifications Friend notifications about the user, each with
 *                                   `id` and `user_id` (the recipient).
 * }
 */
function bb_friends_remove_data_related_users( $user_id, $related = null ) {
	static $store = array();

	$user_id = (int) $user_id;

	if ( null !== $related ) {
		$store[ $user_id ] = $related;

		return $related;
	}

	$related = isset( $store[ $user_id ] ) ? $store[ $user_id ] : array();
	unset( $store[ $user_id ] );

	return $related;
}

/**
 * Capture the members related to a user before their connection data is removed.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id ID of the user whose connection data is being removed.
 */
function bb_friends_capture_related_users_before_remove_data( $user_id ) {
	global $wpdb;

	$user_id = (int) $user_id;
	if ( empty( $user_id ) ) {
		return;
	}

	$bp = buddypress();

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the rows about to be deleted so their caches can be cleared.
	$friendships = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id FROM {$bp->friends->table_name} WHERE initiator_user_id = %d OR friend_user_id = %d", $user_id, $user_id ) );

	$user_ids = array();
	foreach ( (array) $friendships as $friendship ) {
		$other_id              = ( (int) $friendship->initiator_user_id === $user_id ) ? (int) $friendship->friend_user_id : (int) $friendship->initiator_user_id;
		$user_ids[ $other_id ] = $other_id;
	}

	$notifications = array();
	if ( bp_is_active( 'notifications' ) ) {
		// Same rows as the friend notification cleanup in BP_Friends_Friendship::delete_all_for_user().
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the rows about to be deleted so their caches can be cleared.
		$notifications = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id FROM {$bp->notifications->table_name} WHERE component_name = 'friends' AND component_action IN ( 'friendship_request', 'friendship_accepted', 'bb_connections_new_request', 'bb_connections_request_accepted' ) AND item_id = %d", $user_id ) );
	}

	bb_friends_remove_data_related_users(
		$user_id,
		array(
			'user_ids'      => array_values( $user_ids ),
			'notifications' => (array) $notifications,
		)
	);
}
add_action( 'friends_before_remove_data', 'bb_friends_capture_related_users_before_remove_data' );

/**
 * Clear the connection and notification caches of the members related to a
 * user after the user's connection data has been removed.
 *
 * Covers members on either side of a confirmed friendship or a pending request,
 * and the recipients of friend notifications about the user. The removed user's
 * own keys are cleared as well, because a member marked as spam stays on the site.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id ID of the user whose connection data was removed.
 */
function bb_friends_clear_related_users_cache_on_remove_data( $user_id ) {
	$user_id = (int) $user_id;
	if ( empty( $user_id ) ) {
		return;
	}

	$related  = bb_friends_remove_data_related_users( $user_id );
	$user_ids = ! empty( $related['user_ids'] ) ? wp_parse_id_list( $related['user_ids'] ) : array();

	$for_user_keys = array( $user_id, 'get_friendship_ids_for_user_' . $user_id );
	$request_keys  = array( $user_id );
	$bp_keys       = array( 'friends_friend_ids_' . $user_id, 'bp_friends_user_query_' . $user_id );
	$status_keys   = array();

	foreach ( $user_ids as $other_id ) {
		$for_user_keys[] = $other_id;
		$for_user_keys[] = 'get_friendship_ids_for_user_' . $other_id;
		$request_keys[]  = $other_id;
		$bp_keys[]       = 'friends_friend_ids_' . $other_id;
		$bp_keys[]       = 'bp_friends_user_query_' . $other_id;
		$bp_keys[]       = 'friendship_id_' . $other_id . '_' . $user_id;
		$bp_keys[]       = 'friendship_id_' . $user_id . '_' . $other_id;
		$status_keys[]   = bp_core_get_incremented_cache_key( $other_id . ':' . $user_id, 'bp_friends' );
		$status_keys[]   = bp_core_get_incremented_cache_key( $user_id . ':' . $other_id, 'bp_friends' );
	}

	foreach (
		array(
			'bp_friends_friendships_for_user' => $for_user_keys,
			'bp_friends_requests'             => $request_keys,
			'bp'                              => $bp_keys,
			'bp_friends'                      => $status_keys,
		) as $group => $keys
	) {
		foreach ( array_chunk( $keys, 500 ) as $chunk ) {
			bb_core_cache_delete_multiple( $chunk, $group );
		}
	}

	// Friend notifications about the user were deleted with a direct query.
	if ( ! empty( $related['notifications'] ) && function_exists( 'bp_notifications_clear_all_for_user_cache' ) ) {
		$recipient_ids    = array();
		$notification_ids = array();

		foreach ( $related['notifications'] as $notification ) {
			$recipient_ids[ (int) $notification->user_id ] = (int) $notification->user_id;
			$notification_ids[]                            = (int) $notification->id;
			wp_cache_delete( 'bp_notifications_check_access_' . $notification->user_id . '_' . $notification->id, 'bp_notifications' );
		}

		foreach ( array_chunk( $notification_ids, 500 ) as $chunk ) {
			bb_core_cache_delete_multiple( $chunk, 'bp_notifications' );
		}

		foreach ( $recipient_ids as $recipient_id ) {
			bp_notifications_clear_all_for_user_cache( $recipient_id );
		}
	}
}
add_action( 'friends_remove_data', 'bb_friends_clear_related_users_cache_on_remove_data', 5 );

// List actions to clear super cached pages on, if super cache is installed.
add_action( 'friends_friendship_rejected', 'bp_core_clear_cache' );
add_action( 'friends_friendship_accepted', 'bp_core_clear_cache' );
add_action( 'friends_friendship_deleted', 'bp_core_clear_cache' );
add_action( 'friends_friendship_requested', 'bp_core_clear_cache' );
