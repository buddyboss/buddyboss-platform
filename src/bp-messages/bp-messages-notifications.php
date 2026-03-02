<?php
/**
 * BuddyBoss Messages Notifications.
 *
 * @package BuddyBoss\Messages\Notifications
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Format notifications for the Messages component.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item id.
 * @param int    $secondary_item_id The secondary item id.
 * @param int    $total_items       The total number of messaging-related notifications
 *                                  waiting for the user.
 * @param string $format            Return value format. 'string' for compatible
 *                                  notifications; 'array' for WP Toolbar. Default: 'string'.
 * @param int    $notification_id   Notification ID.
 * @param string $screen            Notification Screen type.
 *
 * @return string|array Formatted notifications.
 */
function messages_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $notification_id = 0, $screen = 'web' ) {
	$total_items = (int) $total_items;
	$text        = '';
	$link        = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/inbox' );
	$title       = __( 'Messages', 'buddyboss' );
	$amount      = 'single';

	if ( 'new_message' === $action ) {
		if ( $total_items > 1 ) {
			$amount = 'multiple';
			$text   = sprintf(
				/* translators: %d total messages */
				__( 'You have %d new messages', 'buddyboss' ),
				$total_items
			);

		} else {
			// Get message thread ID.
			$message   = new BP_Messages_Message( $item_id );
			$thread_id = $message->thread_id;
			$link      = ( ! empty( $thread_id ) )
				? bp_get_message_thread_view_link( $thread_id )
				: false;

			if ( ! empty( $secondary_item_id ) ) {

				if ( bp_is_active( 'groups' ) && true === bp_disable_group_messages() ) {

					$group         = bp_messages_get_meta( $item_id, 'group_id', true ); // group id.
					$message_users = bp_messages_get_meta( $item_id, 'group_message_users', true ); // all - individual.
					$message_type  = bp_messages_get_meta( $item_id, 'group_message_type', true ); // open - private.
					$message_from  = bp_messages_get_meta( $item_id, 'message_from', true ); // group.
					$group_name    = bp_get_group_name( groups_get_group( $group ) );

					if ( empty( $message_from ) ) {
						$text = sprintf(
							/* translators: %s user name */
							__( '%s sent you a new private message', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id )
						);
					} elseif ( 'group' === $message_from && 'open' === $message_type && 'individual' === $message_users ) {
						$text = sprintf(
							/* translators: %1$s and %2$s is replaced with the username and group name */
							__( '%1$s sent you a new private message from the group: %2$s', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id ),
							$group_name
						);
					} elseif ( 'group' === $message_from && 'open' === $message_type && 'all' === $message_users ) {
						$text = sprintf(
							/* translators: %1$s and %2$s is replaced with the username and group name */
							__( '%1$s sent you a new group message from the group: %2$s', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id ),
							$group_name
						);
					} elseif ( 'group' === $message_from && 'private' === $message_type && 'all' === $message_users ) {
						$text = sprintf(
							/* translators: %1$s and %2$s is replaced with the username and group name */
							__( '%1$s sent you a new private message from the group: %2$s', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id ),
							$group_name
						);
					} elseif ( 'group' === $message_from && 'private' === $message_type && 'individual' === $message_users && isset( $secondary_item_id ) && ! bp_core_get_user_displayname( $secondary_item_id ) ) {
						$text = sprintf(
							/* translators: %1$s and %2$s is replaced with the username and group name */
							__( '%1$s sent you a new private message from the group: %2$s', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id ),
							$group_name
						);
					} else {
						$text = sprintf(
							/* translators: %s user name */
							__( '%s sent you a new private message', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id )
						);
					}
				} else {
					$text = sprintf(
						/* translators: %s user name */
						__( '%s sent you a new private message', 'buddyboss' ),
						bp_core_get_user_displayname( $secondary_item_id )
					);
				}
			} else {
				$text = sprintf(
					/* translators: Number of total private messages */
					_n( 'You have %s new private message', 'You have %s new private messages', $total_items, 'buddyboss' ),
					bp_core_number_format( $total_items )
				);
			}
		}

		if ( 'string' === $format ) {
			if ( ! empty( $link ) ) {
				$return = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
			} else {
				$return = esc_html( $text );
			}

			/**
			 * Filters the new message notification text before the notification is created.
			 *
			 * This is a dynamic filter. Possible filter names are:
			 *   - 'bp_messages_multiple_new_message_notification'.
			 *   - 'bp_messages_single_new_message_notification'.
			 *
			 * @param string $return            Notification text.
			 * @param int    $total_items       Number of messages referred to by the notification.
			 * @param string $text              The raw notification test (ie, not wrapped in a link).
			 * @param int    $item_id           ID of the associated item.
			 * @param int    $secondary_item_id ID of the secondary associated item.
			 */
			$return = apply_filters( 'bp_messages_' . $amount . '_new_message_notification', $return, (int) $total_items, $text, $link, $item_id, $secondary_item_id );
		} else {
			/** This filter is documented in bp-messages/bp-messages-notifications.php */
			$return = apply_filters(
				'bp_messages_' . $amount . '_new_message_notification',
				array(
					'text' => $text,
					'link' => $link,
				),
				$link,
				(int) $total_items,
				$text,
				$link,
				$item_id,
				$secondary_item_id
			);
		}

		// Custom notification action for the Messages component.
	} else {
		if ( 'string' === $format ) {
			$return = $text;
		} else {
			$return = array(
				'text' => $text,
				'link' => $link,
			);
		}

		/**
		 * Backcompat for plugins that used to filter bp_messages_single_new_message_notification
		 * for their custom actions. These plugins should now use 'bp_messages_' . $action . '_notification'
		 */
		if ( has_filter( 'bp_messages_single_new_message_notification' ) ) {
			if ( 'string' === $format ) {
				/** This filter is documented in bp-messages/bp-messages-notifications.php */
				$return = apply_filters( 'bp_messages_single_new_message_notification', $return, (int) $total_items, $text, $link, $item_id, $secondary_item_id );

				// Notice that there are seven parameters instead of six? Ugh...
			} else {
				/** This filter is documented in bp-messages/bp-messages-notifications.php */
				$return = apply_filters( 'bp_messages_single_new_message_notification', $return, $link, (int) $total_items, $text, $link, $item_id, $secondary_item_id );
			}
		}

		/**
		 * Filters the custom action notification before the notification is created.
		 *
		 * This is a dynamic filter based on the message notification action.
		 *
		 * @since BuddyPress 2.6.0
		 *
		 * @param array  $value             An associative array containing the text and the link of the notification
		 * @param int    $item_id           ID of the associated item.
		 * @param int    $secondary_item_id ID of the secondary associated item.
		 * @param int    $total_items       Number of messages referred to by the notification.
		 * @param string $format            Return value format. 'string' for BuddyBar-compatible
		 *                                  notifications; 'array' for WP Toolbar. Default: 'string'.
		 */
		$return = apply_filters( "bp_messages_{$action}_notification", $return, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen );
	}

	/**
	 * Fires right before returning the formatted message notifications.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $action            The type of message notification.
	 * @param int    $item_id           The primary item ID.
	 * @param int    $secondary_item_id The secondary item ID.
	 * @param int    $total_items       Total amount of items to format.
	 */
	do_action( 'messages_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return $return;
}

/**
 * Send notifications to message recipients.
 *
 * @since BuddyPress 1.9.0
 *
 * @param BP_Messages_Message $message Message object.
 */
function bp_messages_message_sent_add_notification( $message ) {
	if ( ! empty( $message->recipients ) ) {

		$message_from = bp_messages_get_meta( $message->id, 'message_from', true ); // group.
		$group        = bp_messages_get_meta( $message->id, 'group_id', true ); // group_id.
		$action       = 'new_message';

		if ( ! bb_enabled_legacy_email_preference() ) {
			$action = 'bb_messages_new';
			if ( 'group' === $message_from ) {
				$action = 'bb_groups_new_message';
			}
		}

		// Disabled the notification for user who archived this thread.
		foreach ( (array) $message->recipients as $r_key => $recipient ) {
			if ( isset( $recipient->is_hidden ) && $recipient->is_hidden ) {
				unset( $message->recipients[ $r_key ] );
			}
		}

		$min_count = (int) apply_filters( 'bb_new_message_notifications_count', 20 );
		if (
			function_exists( 'bb_notifications_background_enabled' ) &&
			true === bb_notifications_background_enabled() &&
			count( $message->recipients ) > $min_count
		) {
			global $bb_background_updater;
			$recipients = (array) $message->recipients;
			$user_ids   = wp_list_pluck( $recipients, 'user_id' );
			$bb_background_updater->data(
				array(
					'type'     => 'notification',
					'group'    => 'group_messages_new_message_notification',
					'data_id'  => $group,
					'priority' => 5,
					'callback' => 'bb_add_background_notifications',
					'args'     => array(
						$user_ids,
						$message->id,
						$message->sender_id,
						buddypress()->messages->id,
						$action,
						bp_core_current_time(),
						true,
						$message->sender_id,
						$group,
					),
				),
			);
			$bb_background_updater->save()->dispatch();
		} else {
			foreach ( (array) $message->recipients as $recipient ) {
				// Check the sender is blocked by/blocked/suspended/deleted recipient or not.
				if (
					function_exists( 'bb_moderation_allowed_specific_notification' ) &&
					bb_moderation_allowed_specific_notification(
						array(
							'type'              => buddypress()->messages->id,
							'group_id'          => $group,
							'recipient_user_id' => $recipient->user_id,
						)
					)
				) {
					continue;
				}
				bp_notifications_add_notification(
					array(
						'user_id'           => $recipient->user_id,
						'item_id'           => $message->id,
						'secondary_item_id' => $message->sender_id,
						'component_name'    => buddypress()->messages->id,
						'component_action'  => $action,
						'date_notified'     => bp_core_current_time(),
						'is_new'            => 1,
					)
				);
			}
		}
	}
}
add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

/**
 * Mark new message notification when member reads a message thread directly.
 *
 * @since BuddyPress 1.9.0
 */
function bp_messages_screen_conversation_mark_notifications() {
	global $thread_template;

	/*
	 * Only run on the logged-in user's profile.
	 * If an admin visits a thread, it shouldn't change the read status.
	 */
	if ( ! bp_is_my_profile() ) {
		return;
	}

	// Get unread PM notifications for the user.
	$new_pm_notifications = BP_Notifications_Notification::get(
		array(
			'user_id'          => bp_loggedin_user_id(),
			'component_name'   => buddypress()->messages->id,
			'component_action' => array( 'new_message', 'bb_groups_new_message', 'bb_messages_new' ),
			'is_new'           => 1,
		)
	);
	$unread_message_ids   = wp_list_pluck( $new_pm_notifications, 'item_id' );

	// No unread PMs, so stop!
	if ( empty( $unread_message_ids ) ) {
		return;
	}

	// Get the unread message ids for this thread only.
	$message_ids = array_intersect( $unread_message_ids, wp_list_pluck( $thread_template->thread->messages, 'id' ) );

	// Mark each notification for each PM message as read.
	foreach ( $message_ids as $message_id ) {
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'new_message' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'bb_messages_new' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'bb_groups_new_message' );
	}
}
add_action( 'thread_loop_start', 'bp_messages_screen_conversation_mark_notifications', 10 );

/**
 * Mark new message notification as read when the corresponding message is mark read.
 *
 * This callback covers mark-as-read bulk actions.
 *
 * @since BuddyPress 3.0.0
 *
 * @param int $thread_id ID of the thread being marked as read.
 */
function bp_messages_mark_notification_on_mark_thread( $thread_id ) {
	$thread_messages = BP_Messages_Thread::get_messages( $thread_id );

	foreach ( $thread_messages as $thread_message ) {
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $thread_message->id, buddypress()->messages->id, 'new_message' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $thread_message->id, buddypress()->messages->id, 'bb_messages_new' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $thread_message->id, buddypress()->messages->id, 'bb_groups_new_message' );
	}
}
add_action( 'messages_thread_mark_as_read', 'bp_messages_mark_notification_on_mark_thread' );

/**
 * When a message is deleted, delete corresponding notifications.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int   $thread_id   ID of the thread.
 * @param array $message_ids IDs of the messages.
 */
function bp_messages_message_delete_notifications( $thread_id, $message_ids ) {
	// For each recipient, delete notifications corresponding to each message.
	$thread = new BP_Messages_Thread( $thread_id );
	foreach ( $thread->get_recipients() as $recipient ) {
		foreach ( $message_ids as $message_id ) {
			bp_notifications_delete_notifications_by_item_id( $recipient->user_id, (int) $message_id, buddypress()->messages->id, 'new_message' );
			bp_notifications_delete_notifications_by_item_id( $recipient->user_id, (int) $message_id, buddypress()->messages->id, 'bb_messages_new' );
			bp_notifications_delete_notifications_by_item_id( $recipient->user_id, (int) $message_id, buddypress()->messages->id, 'bb_groups_new_message' );
		}
	}
}
add_action( 'bp_messages_thread_after_delete', 'bp_messages_message_delete_notifications', 10, 2 );


