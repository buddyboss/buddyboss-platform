<?php
/**
 * BuddyBoss Connections Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyBoss\Connections\Notifications
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notification formatting callback for bp-friends notifications.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item ID.
 * @param int    $secondary_item_id The secondary item ID.
 * @param int    $total_items       The total number of messaging-related notifications
 *                                  waiting for the user.
 * @param string $format            'string' for BuddyBar-compatible notifications;
 *                                  'array' for WP Toolbar. Default: 'string'.
 * @param int    $notification_id   The notification ID.
 * @param string $screen            The screen.
 *
 * @return array|string
 */
function bboss_friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $notification_id = 0, $screen = 'web' ) {

	switch ( $action ) {
		case 'friendship_accepted':
			$link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_friends_slug() . '/my-friends' );

			// $action and $amount are used to generate dynamic filter names.
			$action = 'accepted';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				/* translators: %d: number of members who accepted connection requests. */
				$text   = sprintf( __( '%d members accepted your connection requests', 'buddyboss-platform' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				/* translators: %s: member display name. */
				$text   = sprintf( __( '%s accepted your request to connect', 'buddyboss-platform' ), bboss_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;

		case 'friendship_request':
			$link = bboss_loggedin_user_domain() . bboss_get_friends_slug() . '/requests/?new';

			$action = 'request';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				/* translators: %d: number of pending connection requests. */
				$text   = sprintf( __( 'You have %d pending requests to connect', 'buddyboss-platform' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				/* translators: %s: member display name. */
				$text   = sprintf( __( '%s sent you an invitation to connect', 'buddyboss-platform' ), bboss_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;

		default:
			/**
			 * Filters plugin-added friends-related custom component_actions.
			 *
			 * @since BuddyBoss 1.9.3
			 *
			 * @param string $notification      Null value.
			 * @param int    $item_id           The primary item ID.
			 * @param int    $secondary_item_id The secondary item ID.
			 * @param int    $total_items       The total number of messaging-related notifications
			 *                                  waiting for the user.
			 * @param string $format            'string' for BuddyBar-compatible notifications;
			 *                                  'array' for WP Toolbar.
			 * @param int    $notification_id   Notification ID.
			 * @param string $screen            Notification Screen type.
			 */
			$custom_action_notification = apply_filters( 'bb_friends_' . $action . '_notification', null, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen );

			if ( ! is_null( $custom_action_notification ) ) {
				return $custom_action_notification;
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format.
	if ( 'string' === $format ) {

		/**
		 * Filters the format of friendship notifications based on type and amount * of notifications pending.
		 *
		 * This is a variable filter that has four possible versions.
		 * The four possible versions are:
		 *   - bp_friends_single_friendship_accepted_notification
		 *   - bp_friends_multiple_friendship_accepted_notification
		 *   - bp_friends_single_friendship_request_notification
		 *   - bp_friends_multiple_friendship_request_notification
		 *
		 * @since BuddyPress 1.0.0
		 * @since BuddyPress 6.0.0 Adds the $secondary_item_id parameter.
		 * @since BuddyBoss 2.3.90 Adds the $secondary_item_id parameter.
		 *
		 * @param string|array $value             Depending on format, an HTML link to new requests profile
		 *                                        tab or array with link and text.
		 * @param int          $total_items       The total number of messaging-related notifications
		 *                                        waiting for the user.
		 * @param int          $item_id           The primary item ID.
		 * @param int          $secondary_item_id The secondary item ID.
		 */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id, $secondary_item_id );
	} else {
		/** This filter is documented in bp-friends/bboss-friends-notifications.php */
		$return = apply_filters(
			'bp_friends_' . $amount . '_friendship_' . $action . '_notification',
			array(
				'link' => $link,
				'text' => $text,
			),
			(int) $total_items,
			$item_id,
			$secondary_item_id
		);
	}

	/**
	 * Fires at the end of the bp-friends notification format callback.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string       $action            The kind of notification being rendered.
	 * @param int          $item_id           The primary item ID.
	 * @param int          $secondary_item_id The secondary item ID.
	 * @param int          $total_items       The total number of messaging-related notifications
	 *                                        waiting for the user.
	 * @param array|string $return            Notification text string or array of link and text.
	 */
	do_action( 'friends_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $return );

	return $return;
}

/**
 * Clear friend-related notifications when ?new=1
 *
 * @since BuddyPress 1.2.0
 */
function bboss_friends_clear_friend_notifications() {
	if ( isset( $_GET['new'] ) ) {
		bboss_notifications_mark_notifications_by_type( bboss_loggedin_user_id(), bboss()->friends->id, 'friendship_accepted' );
		bboss_notifications_mark_notifications_by_type( bboss_loggedin_user_id(), bboss()->friends->id, 'bb_connections_request_accepted' );
	}
}
add_action( 'bp_activity_screen_my_activity', 'bboss_friends_clear_friend_notifications' );

/**
 * Delete any friendship request notifications for the logged in user.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_friends_mark_friendship_request_notifications_by_type() {
	if ( isset( $_GET['new'] ) ) {
		bboss_notifications_mark_notifications_by_type( bboss_loggedin_user_id(), bboss()->friends->id, 'friendship_request' );
		bboss_notifications_mark_notifications_by_type( bboss_loggedin_user_id(), bboss()->friends->id, 'bb_connections_new_request' );
	}
}
add_action( 'friends_screen_requests', 'bboss_friends_mark_friendship_request_notifications_by_type' );

/**
 * Delete any friendship acceptance notifications for the logged in user.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_friends_mark_friendship_accepted_notifications_by_type() {
	bboss_notifications_mark_notifications_by_type( bboss_loggedin_user_id(), bboss()->friends->id, 'friendship_accepted' );
	bboss_notifications_mark_notifications_by_type( bboss_loggedin_user_id(), bboss()->friends->id, 'bb_connections_request_accepted' );
}
add_action( 'friends_screen_my_friends', 'bboss_friends_mark_friendship_accepted_notifications_by_type' );

/**
 * Notify one use that another user has requested their virtual friendship.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bboss_friends_friendship_requested_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {

	if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $friend_user_id, $initiator_user_id ) ) {
		return;
	}

	$action = 'friendship_request';
	if ( ! bboss_enabled_legacy_email_preference() ) {
		$action = 'bb_connections_new_request';
	}

	bboss_notifications_add_notification(
		array(
			'user_id'           => $friend_user_id,
			'item_id'           => $initiator_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => bboss()->friends->id,
			'component_action'  => $action,
			'date_notified'     => bboss_core_current_time(),
			'is_new'            => 1,
		)
	);
}
add_action( 'friends_friendship_requested', 'bboss_friends_friendship_requested_notification', 10, 3 );

/**
 * Remove friend request notice when a member rejects another members
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship object.
 */
function bboss_friends_mark_friendship_rejected_notifications_by_item_id( $friendship_id, $friendship ) {
	bboss_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, bboss()->friends->id, 'friendship_request' );
	bboss_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, bboss()->friends->id, 'bb_connections_new_request' );
}
add_action( 'friends_friendship_rejected', 'bboss_friends_mark_friendship_rejected_notifications_by_item_id', 10, 2 );

/**
 * Notify a member when another member accepts their virtual friendship request.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bboss_friends_add_friendship_accepted_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {

	if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $initiator_user_id, $friend_user_id ) ) {
		return;
	}

	// Remove the friend request notice.
	bboss_notifications_mark_notifications_by_item_id( $friend_user_id, $initiator_user_id, bboss()->friends->id, 'friendship_request' );
	bboss_notifications_mark_notifications_by_item_id( $friend_user_id, $initiator_user_id, bboss()->friends->id, 'bb_connections_new_request' );

	$action = 'friendship_accepted';

	if ( ! bboss_enabled_legacy_email_preference() ) {
		$action = 'bb_connections_request_accepted';
	}

	// Add a friend accepted notice for the initiating user.
	bboss_notifications_add_notification(
		array(
			'user_id'           => $initiator_user_id,
			'item_id'           => $friend_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => bboss()->friends->id,
			'component_action'  => $action,
			'date_notified'     => bboss_core_current_time(),
			'is_new'            => 1,
		)
	);
}
add_action( 'friends_friendship_accepted', 'bboss_friends_add_friendship_accepted_notification', 10, 3 );

/**
 * Remove friend request notice when a member withdraws their friend request.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship Object.
 */
function bboss_friends_mark_friendship_withdrawn_notifications_by_item_id( $friendship_id, $friendship ) {
	bboss_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, bboss()->friends->id, 'friendship_request' );
	bboss_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, bboss()->friends->id, 'bb_connections_new_request' );
}
add_action( 'friends_friendship_withdrawn', 'bboss_friends_mark_friendship_withdrawn_notifications_by_item_id', 10, 2 );

/**
 * Remove connection requests FROM user, used primarily when a user is deleted.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id ID of the user whose notifications are removed.
 */
function bboss_friends_remove_notifications_data( $user_id = 0 ) {
	bboss_notifications_delete_notifications_from_user( $user_id, bboss()->friends->id, 'friendship_request' );
	bboss_notifications_delete_notifications_from_user( $user_id, bboss()->friends->id, 'bb_connections_new_request' );
}
add_action( 'friends_remove_data', 'bboss_friends_remove_notifications_data', 10, 1 );

