<?php
/**
 * BuddyBoss Connections Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyBoss\Connections\Activity
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Record an activity item related to the Connections component.
 *
 * A wrapper for {@link bp_activity_add()} that provides some Connections-specific
 * defaults.
 *
 * @since BuddyPress 1.0.0
 *
 * @see bp_activity_add() for more detailed description of parameters and
 *      return values.
 *
 * @param array|string $args {
 *     An array of arguments for the new activity item. Accepts all parameters
 *     of {@link bp_activity_add()}. The one difference is the following
 *     argument, which has a different default here:
 *     @type string $component Default: the id of your Connections component
 *                             (usually 'friends').
 * }
 * @return WP_Error|bool|int See {@link bp_activity_add()}.
 */
function friends_record_activity( $args = '' ) {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'user_id'           => bp_loggedin_user_id(),
			'action'            => '',
			'content'           => '',
			'primary_link'      => '',
			'component'         => buddypress()->friends->id,
			'type'              => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'recorded_time'     => bp_core_current_time(),
			'hide_sitewide'     => false,
		)
	);

	return bp_activity_add( $r );
}

/**
 * Delete an activity item related to the Connections component.
 *
 * @since BuddyPress 1.0.0
 *
 * @param array $args {
 *     An array of arguments for the item to delete.
 *     @type int    $item_id ID of the 'item' associated with the activity item.
 *                           For Connections activity items, this is usually the user ID of one
 *                           of the friends.
 *     @type string $type    The 'type' of the activity item (eg
 *                           'friendship_accepted').
 *     @type int    $user_id ID of the user associated with the activity item.
 * }
 * @return bool True on success, false on failure.
 */
function friends_delete_activity( $args ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	bp_activity_delete_by_item_id(
		array(
			'component' => buddypress()->friends->id,
			'item_id'   => $args['item_id'],
			'type'      => $args['type'],
			'user_id'   => $args['user_id'],
		)
	);
}

/**
 * Register the activity actions for bp-friends.
 *
 * @since BuddyPress 1.1.0
 */
function friends_register_activity_actions() {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$bp = buddypress();

	// These two added in BP 1.6.
	bp_activity_set_action(
		$bp->friends->id,
		'friendship_accepted',
		__( 'Connections accepted', 'buddyboss' ),
		'bp_friends_format_activity_action_friendship_accepted',
		__( 'Connections', 'buddyboss' ),
		array( 'activity', 'member' )
	);

	bp_activity_set_action(
		$bp->friends->id,
		'friendship_created',
		__( 'New connections', 'buddyboss' ),
		'bp_friends_format_activity_action_friendship_created',
		__( 'Connections', 'buddyboss' ),
		array( 'activity', 'member' )
	);

	// < BP 1.6 backpat.
	bp_activity_set_action( $bp->friends->id, 'friends_register_activity_action', __( 'New connection created', 'buddyboss' ) );

	/**
	 * Fires after all default bp-friends activity actions have been registered.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'friends_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'friends_register_activity_actions' );

/**
 * Format 'friendship_accepted' activity actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $action   Activity action string.
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function bp_friends_format_activity_action_friendship_accepted( $action, $activity ) {
	$initiator_link = bp_core_get_userlink( $activity->user_id );
	$friend_link    = bp_core_get_userlink( $activity->secondary_item_id );

	$action = sprintf( __( '%1$s and %2$s are now connected', 'buddyboss' ), $initiator_link, $friend_link );

	// Backward compatibility for legacy filter
	// The old filter has the $friendship object passed to it. We want to
	// avoid having to build this object if it's not necessary.
	if ( has_filter( 'friends_activity_friendship_accepted_action' ) ) {
		$friendship = new BP_Friends_Friendship( $activity->item_id );
		$action     = apply_filters( 'friends_activity_friendsip_accepted_action', $action, $friendship );
	}

	/**
	 * Filters the 'friendship_accepted' activity action format.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param string $action   String text for the 'friendship_accepted' action.
	 * @param object $activity Activity data.
	 */
	return apply_filters( 'bp_friends_format_activity_action_friendship_accepted', $action, $activity );
}

/**
 * Format 'friendship_created' activity actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function bp_friends_format_activity_action_friendship_created( $action, $activity ) {
	$initiator_link = bp_core_get_userlink( $activity->user_id );
	$friend_link    = bp_core_get_userlink( $activity->secondary_item_id );

	$action = sprintf( __( '%1$s and %2$s are now connected', 'buddyboss' ), $initiator_link, $friend_link );

	// Backward compatibility for legacy filter
	// The old filter has the $friendship object passed to it. We want to
	// avoid having to build this object if it's not necessary.
	if ( has_filter( 'friends_activity_friendship_accepted_action' ) ) {
		$friendship = new BP_Friends_Friendship( $activity->item_id );
		$action     = apply_filters( 'friends_activity_friendsip_accepted_action', $action, $friendship );
	}

	/**
	 * Filters the 'friendship_created' activity action format.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param string $action   String text for the 'friendship_created' action.
	 * @param object $activity Activity data.
	 */
	return apply_filters( 'bp_friends_format_activity_action_friendship_created', $action, $activity );
}

/**
 * Fetch data related to friended users at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $activities Array of activity items.
 * @return array
 */
function bp_friends_prefetch_activity_object_data( $activities ) {
	if ( empty( $activities ) ) {
		return $activities;
	}

	$friend_ids = array();

	foreach ( $activities as $activity ) {
		if ( buddypress()->friends->id !== $activity->component ) {
			continue;
		}

		$friend_ids[] = $activity->secondary_item_id;
	}

	if ( ! empty( $friend_ids ) ) {
		// Fire a user query to prime user caches.
		new BP_User_Query(
			array(
				'user_ids'          => $friend_ids,
				'populate_extras'   => false,
				'update_meta_cache' => false,
			)
		);
	}

	return $activities;
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_friends_prefetch_activity_object_data' );

/**
 * Set up activity arguments for use with the 'friends' scope.
 *
 * For details on the syntax, see {@link BP_Activity_Query}.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_friends_filter_activity_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	if ( bp_is_single_activity() ) {
		$user_id = bp_loggedin_user_id();
	}

	$mutual_friends = array();

	// Determine friends of user.
	$friends = friends_get_friend_user_ids( $user_id );

	if ( is_user_logged_in() ) {
		$mutual_friends = friends_get_friend_user_ids( bp_loggedin_user_id() );
		if ( bp_loggedin_user_id() !== $user_id && in_array( $user_id, $mutual_friends ) ) {
			$mutual_friends[] = bp_loggedin_user_id();
		}
		if ( ! empty( $mutual_friends ) ) {
			$mutual_friends = array_intersect( $friends, $mutual_friends );
		}
	}

	//Determine activity tabs status and filter the activity.
	if ( is_user_logged_in() && bp_loggedin_user_id() === $user_id ) {
		$friends[]        = bp_loggedin_user_id();
		$mutual_friends[] = bp_loggedin_user_id();
	}

	if ( empty( $friends ) ) {
		$friends = array( 0 );
	}

	if ( empty( $mutual_friends ) ) {
		$mutual_friends = array( 0 );
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array(
				array(
					'column'  => 'user_id',
					'compare' => 'IN',
					'value'   => (array) $friends,
				),
				array(
					'column'  => 'privacy',
					'compare' => 'IN',
					'value'   => $privacy,
				),
			),
			array(
				array(
					'column'  => 'user_id',
					'compare' => 'IN',
					'value'   => (array) $mutual_friends,
				),
				array(
					'column'  => 'privacy',
					'compare' => 'IN',
					'value'   => 'friends',
				),
			),
		),
		// We should only be able to view sitewide activity content for friends.
		array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		),

		// Overrides.
		'override' => array(
			'filter'      => array( 'user_id' => 0 ),
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_friends_scope_args', 'bp_friends_filter_activity_scope', 10, 2 );

/**
 * Set up activity arguments for use with the 'just-me' scope.
 *
 * For details on the syntax, see {@link BP_Activity_Query}.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_friends_filter_activity_just_me_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Get the requested action.
	$action = isset( $filter['filter']['action'] ) ? $filter['filter']['action'] : array();

	// Make sure actions are listed in an array.
	if ( ! is_array( $action ) ) {
		$action = explode( ',', $filter['filter']['action'] );
	}

	$action = array_flip( array_filter( $action ) );

	/**
	 * If filtering activities for something other than the friendship_created
	 * action return without changing anything
	 */
	if ( ! empty( $action ) && ! isset( $action['friendship_created'] ) ) {
		return $retval;
	}

	// Juggle existing override value.
	$override = array();
	if ( ! empty( $retval['override'] ) ) {
		$override = $retval['override'];
		unset( $retval['override'] );
	}

	/**
	 * Else make sure to get the friendship_created action, the user is involved in
	 * - user initiated the friendship
	 * - user has been requested a friendship
	 */
	$retval = array(
		'relation' => 'OR',
		$retval,
		array(
			'relation' => 'AND',
			array(
				'column' => 'component',
				'value'  => 'friends',
			),
			array(
				'column' => 'secondary_item_id',
				'value'  => $user_id,
			),
		),
	);

	// Juggle back override value.
	if ( ! empty( $override ) ) {
		$retval['override'] = $override;
	}

	return $retval;
}
add_filter( 'bp_activity_set_just-me_scope_args', 'bp_friends_filter_activity_just_me_scope', 20, 2 );

/**
 * Add activity feed items when one members accepts another members request
 * for virtual friendship.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int         $friendship_id       ID of the friendship.
 * @param int         $initiator_user_id   ID of friendship initiator.
 * @param int         $friend_user_id      ID of user whose friendship is requested.
 * @param object|bool $friendship Optional Friendship object.
 */
function bp_friends_friendship_accepted_activity( $friendship_id, $initiator_user_id, $friend_user_id, $friendship = false ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Record in activity feeds for the initiator.
	friends_record_activity(
		array(
			'user_id'           => $initiator_user_id,
			'type'              => 'friendship_created',
			'item_id'           => $friendship_id,
			'secondary_item_id' => $friend_user_id,
		)
	);
}
add_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );

/**
 * Deletes friendship activity items when a user is deleted.
 *
 * @since BuddyPress 2.5.0
 *
 * @param int $user_id The ID of the user being deleted.
 */
function bp_friends_delete_activity_on_user_delete( $user_id = 0 ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	bp_activity_delete(
		array(
			'component'         => buddypress()->friends->id,
			'type'              => 'friendship_created',
			'secondary_item_id' => $user_id,
		)
	);
}
add_action( 'friends_remove_data', 'bp_friends_delete_activity_on_user_delete' );

/**
 * Remove friendship activity item when a friendship is deleted.
 *
 * @since BuddyPress 3.2.0
 *
 * @param int $friendship_id ID of the friendship.
 */
function bp_friends_delete_activity_on_friendship_delete( $friendship_id ) {
	friends_delete_activity(
		array(
			'item_id' => $friendship_id,
			'type'    => 'friendship_created',
			'user_id' => 0,
		)
	);
}
add_action( 'friends_friendship_deleted', 'bp_friends_delete_activity_on_friendship_delete' );

/**
 * Stop sending notification when user mentioned in activity and not in friend list.
 *
 * @since BuddyBoss 1.4.3
 *
 * @param bool                 $send      Whether or not BuddyBoss should send a notification to the mentioned users.
 * @param array                $usernames Array of users potentially notified.
 * @param int                  $user_id   ID of the current user being notified.
 * @param BP_Activity_Activity $activity  Activity object.
 *
 * @return bool
 */
function bp_friends_activity_at_name_do_notifications( $send, $usernames, $user_id, $activity ) {

	if ( 'friends' !== $activity->privacy ) {
		return $send;
	}

	$friends = friends_get_friend_user_ids( $activity->user_id );


	if ( ! empty( $friends ) && in_array( $user_id, $friends ) ) {
		return $send;
	}

	return false;
}
add_filter( 'bp_activity_at_name_do_notifications', 'bp_friends_activity_at_name_do_notifications', 10, 4 );
