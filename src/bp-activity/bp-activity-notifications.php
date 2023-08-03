<?php
/**
 * BuddyBoss Activity Notifications.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Format notifications related to activity.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $action            The type of activity item. Just 'new_at_mention' for now.
 * @param int    $item_id           The activity ID.
 * @param int    $secondary_item_id In the case of at-mentions, this is the mentioner's ID.
 * @param int    $total_items       The total number of notifications to format.
 * @param string $format            'string' to get a BuddyBar-compatible notification, 'array' otherwise.
 * @param int    $id                Optional. The notification ID.
 * @param string $screen            Notification Screen type.
 * @return string $return Formatted @mention notification.
 */
function bp_activity_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $id = 0, $screen = 'web' ) {
	$action_filter = $action;
	$return        = false;
	$activity_id   = $item_id;
	$user_id       = $secondary_item_id;
	$user_fullname = bp_core_get_user_displayname( $user_id );
	$amount        = '';
	$text          = '';
	$link          = '';

	switch ( $action ) {
		case 'new_at_mention':
			$action_filter = 'at_mentions';
			$link          = bp_activity_get_permalink( $item_id );
			$title         = sprintf( __( '@%s Mentions', 'buddyboss' ), bp_get_loggedin_user_username() );
			$amount        = 'single';

			/**
			 * Filters the mention notification permalink.
			 *
			 * The two possible hooks are bp_activity_new_at_mention_permalink
			 * or activity_get_notification_permalink.
			 *
			 * @since BuddyBoss 1.2.5
			 *
			 * @param string $link          HTML anchor tag for the interaction.
			 * @param int    $item_id            The permalink for the interaction.
			 * @param int    $secondary_item_id     How many items being notified about.
			 * @param int    $total_items     ID of the activity item being formatted.
			 */
			$link = apply_filters( 'bp_activity_new_at_mention_permalink', $link, $item_id, $secondary_item_id, $total_items );

			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( 'You have %1$d new mentions', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text = sprintf( __( '%1$s mentioned you', 'buddyboss' ), $user_fullname );
			}
			break;

		case 'update_reply':
			$link   = bp_get_notifications_permalink();
			$title  = __( 'New Activity reply', 'buddyboss' );
			$amount = 'single';

			if ( (int) $total_items > 1 ) {
				$link   = add_query_arg( 'type', $action, $link );
				$text   = sprintf( __( 'You have %1$d new replies', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$link = add_query_arg( 'rid', (int) $id, bp_activity_get_permalink( $activity_id ) );
				$text = sprintf( __( '%1$s commented on one of your updates', 'buddyboss' ), $user_fullname );
			}
			break;

		case 'comment_reply':
			$link   = bp_get_notifications_permalink();
			$title  = __( 'New Activity comment reply', 'buddyboss' );
			$amount = 'single';

			if ( (int) $total_items > 1 ) {
				$link   = add_query_arg( 'type', $action, $link );
				$text   = sprintf( __( 'You have %1$d new comment replies', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$link = add_query_arg( 'crid', (int) $id, bp_activity_get_permalink( $activity_id ) );
				$text = sprintf( __( '%1$s replied to one of your activity comments', 'buddyboss' ), $user_fullname );
			}
			break;

		default:

			/**
			 * Filters plugin-added activity-related custom component_actions.
			 *
			 * @since BuddyBoss 1.9.3
			 *
			 * @param string $notification      Null value.
			 * @param int    $item_id           The primary item ID.
			 * @param int    $secondary_item_id The secondary item ID.
			 * @param int    $total_items       The total number of messaging-related notifications
			 *                                  waiting for the user.
			 * @param string $format            'string' for compatible notifications;
			 *                                  'array' for WP Toolbar.
			 * @param int    $id                Notification ID.
			 * @param string $screen            Notification Screen type.
			 */
			$custom_action_notification = apply_filters( 'bp_activity_' . $action . '_notification', null, $item_id, $secondary_item_id, $total_items, $format, $id, $screen );

			if ( ! is_null( $custom_action_notification ) ) {
				return $custom_action_notification;
			}

			break;
	}

	if ( 'string' == $format ) {

		/**
		 * Filters the activity notification for the string format.
		 *
		 * This is a variable filter that is dependent on how many items
		 * need notified about. The two possible hooks are bp_activity_single_at_mentions_notification
		 * or bp_activity_multiple_at_mentions_notification.
		 *
		 * @since BuddyPress 1.5.0
		 * @since BuddyPress 2.6.0 use the $action_filter as a new dynamic portion of the filter name.
		 *
		 * @param string $string          HTML anchor tag for the interaction.
		 * @param string $link            The permalink for the interaction.
		 * @param int    $total_items     How many items being notified about.
		 * @param int    $activity_id     ID of the activity item being formatted.
		 * @param int    $user_id         ID of the user who inited the interaction.
		 */
		$return = apply_filters( 'bp_activity_' . $amount . '_' . $action_filter . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', $link, (int) $total_items, $activity_id, $user_id );
	} else {

		/**
		 * Filters the activity notification for any non-string format.
		 *
		 * This is a variable filter that is dependent on how many items need notified about.
		 * The two possible hooks are bp_activity_single_at_mentions_notification
		 * or bp_activity_multiple_at_mentions_notification.
		 *
		 * @since BuddyPress 1.5.0
		 * @since BuddyPress 2.6.0 use the $action_filter as a new dynamic portion of the filter name.
		 *
		 * @param array  $array           Array holding the content and permalink for the interaction notification.
		 * @param string $link            The permalink for the interaction.
		 * @param int    $total_items     How many items being notified about.
		 * @param int    $activity_id     ID of the activity item being formatted.
		 * @param int    $user_id         ID of the user who inited the interaction.
		 */
		$return = apply_filters(
			'bp_activity_' . $amount . '_' . $action_filter . '_notification',
			array(
				'text' => $text,
				'link' => $link,
			),
			$link,
			(int) $total_items,
			$activity_id,
			$user_id
		);
	}

	/**
	 * Fires right before returning the formatted activity notifications.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $action            The type of activity item.
	 * @param int    $item_id           The activity ID.
	 * @param int    $secondary_item_id The user ID who inited the interaction.
	 * @param int    $total_items       Total amount of items to format.
	 */
	do_action( 'activity_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return $return;
}

/**
 * Notify a member when their nicename is mentioned in an activity feed item.
 *
 * Hooked to the 'bp_activity_sent_mention_email' action, we piggy back off the
 * existing email code for now, since it does the heavy lifting for us. In the
 * future when we separate emails from Notifications, this will need its own
 * 'bp_activity_at_name_send_emails' equivalent helper function.
 *
 * @since BuddyPress 1.9.0
 *
 * @param object $activity           Activity object.
 * @param string $subject (not used) Notification subject.
 * @param string $message (not used) Notification message.
 * @param string $content (not used) Notification content.
 * @param int    $receiver_user_id   ID of user receiving notification.
 */
function bp_activity_at_mention_add_notification( $activity, $subject, $message, $content, $receiver_user_id ) {

	// Specify the Notification type.
	$component_action = 'new_at_mention';
	$component_name   = buddypress()->activity->id;

	if ( ! bb_enabled_legacy_email_preference() ) {
		$component_action = 'bb_new_mention';
	}

	add_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );

	bp_notifications_add_notification(
		array(
			'user_id'           => $receiver_user_id,
			'item_id'           => $activity->id,
			'secondary_item_id' => $activity->user_id,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);

	remove_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );

}
add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );

/**
 * Notify a member one of their activity received a reply.
 *
 * @since BuddyPress 2.6.0
 *
 * @param BP_Activity_Activity $activity     The original activity.
 * @param int                  $comment_id   ID for the newly received comment.
 * @param int                  $commenter_id ID of the user who made the comment.
 */
function bp_activity_update_reply_add_notification( $activity, $comment_id, $commenter_id ) {

	if (
		function_exists( 'bb_moderation_allowed_specific_notification' ) &&
		bb_moderation_allowed_specific_notification(
			array(
				'type'              => buddypress()->activity->id,
				'group_id'          => 'groups' === $activity->component ? $activity->item_id : '',
				'recipient_user_id' => $activity->user_id,
				'sender_id'         => $activity->user_id,
			)
		)
	) {
		return;
	}

	// Specify the Notification type.
	$component_action = 'update_reply';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$component_action = 'bb_activity_comment';
	}

	add_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );
	add_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );

	bp_notifications_add_notification(
		array(
			'user_id'           => $activity->user_id,
			'item_id'           => $comment_id,
			'secondary_item_id' => $commenter_id,
			'component_name'    => buddypress()->activity->id,
			'component_action'  => $component_action,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
	remove_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
	remove_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );

}
add_action( 'bp_activity_sent_reply_to_update_notification', 'bp_activity_update_reply_add_notification', 10, 3 );

/**
 * Notify a member one of their activity comment received a reply.
 *
 * @since BuddyPress 2.6.0
 *
 * @param BP_Activity_Activity $activity_comment The parent activity.
 * @param int                  $comment_id       ID for the newly received comment.
 * @param int                  $commenter_id     ID of the user who made the comment.
 */
function bp_activity_comment_reply_add_notification( $activity_comment, $comment_id, $commenter_id ) {

	$original_activity = new BP_Activity_Activity( $activity_comment->item_id );
	if (
		function_exists( 'bb_moderation_allowed_specific_notification' ) &&
		bb_moderation_allowed_specific_notification(
			array(
				'type'              => buddypress()->activity->id,
				'group_id'          => 'groups' === $original_activity->component ? $original_activity->item_id : '',
				'recipient_user_id' => $activity_comment->user_id,
				'sender_id'         => $original_activity->user_id,
			)
		)
	) {
		return;
	}

	// Specify the Notification type.
	$component_action = 'comment_reply';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$component_action = 'bb_activity_comment';
	}

	add_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );
	add_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );

	bp_notifications_add_notification(
		array(
			'user_id'           => $activity_comment->user_id,
			'item_id'           => $comment_id,
			'secondary_item_id' => $commenter_id,
			'component_name'    => buddypress()->activity->id,
			'component_action'  => $component_action,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
	remove_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
	remove_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );
}
add_action( 'bp_activity_sent_reply_to_reply_notification', 'bp_activity_comment_reply_add_notification', 10, 3 );

/**
 * Mark at-mention notifications as read when users visit their Mentions page.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyPress 2.5.0 Add the $user_id parameter
 *
 * @param int $user_id The id of the user whose notifications are marked as read.
 */
function bp_activity_remove_screen_notifications( $user_id = 0 ) {
	// Only mark read if the current user is looking at his own mentions.
	if ( empty( $user_id ) || (int) $user_id !== (int) bp_loggedin_user_id() ) {
		return;
	}

	bp_notifications_mark_notifications_by_type( $user_id, buddypress()->activity->id, 'new_at_mention' );
}
add_action( 'bp_activity_clear_new_mentions', 'bp_activity_remove_screen_notifications', 10, 1 );

/**
 * Mark notifications as read when a user visits an activity permalink.
 *
 * @since BuddyPress 2.0.0
 * @since BuddyPress 3.2.0 Marks replies to parent update and replies to an activity comment as read.
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_activity_remove_screen_notifications_single_activity_permalink( $activity ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Mark as read any notifications for the current user related to this activity item.
	bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $activity->id, buddypress()->activity->id, 'new_at_mention' );
	bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $activity->id, buddypress()->activity->id, 'bb_new_mention' );
	bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $activity->id, buddypress()->activity->id, 'bb_activity_following_post' );

	$comment_id = 0;
	// For replies to a parent update.
	if ( ! empty( $_GET['rid'] ) ) {
		$comment_id = (int) $_GET['rid'];

		// For replies to an activity comment.
	} elseif ( ! empty( $_GET['crid'] ) ) {
		$comment_id = (int) $_GET['crid'];
	}

	// Mark individual activity reply notification as read.
	if ( ! empty( $comment_id ) ) {
		BP_Notifications_Notification::update(
			array(
				'is_new' => false,
			),
			array(
				'user_id' => bp_loggedin_user_id(),
				'id'      => $comment_id,
			)
		);
	}
}
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_activity_remove_screen_notifications_single_activity_permalink' );

/**
 * Mark non-mention notifications as read when user visits our read permalink.
 *
 * In particular, 'update_reply' and 'comment_reply' notifications are handled
 * here. See {@link bp_activity_format_notifications()} for more info.
 *
 * @since BuddyPress 2.6.0
 */
function bp_activity_remove_screen_notifications_for_non_mentions() {
	if ( false === is_singular() || false === is_user_logged_in() || empty( $_GET['nid'] ) ) {
		return;
	}

	// Mark notification as read.
	BP_Notifications_Notification::update(
		array(
			'is_new' => false,
		),
		array(
			'user_id' => bp_loggedin_user_id(),
			'id'      => (int) $_GET['nid'],
		)
	);
}
add_action( 'bp_screens', 'bp_activity_remove_screen_notifications_for_non_mentions' );

/**
 * Delete at-mention notifications when the corresponding activity item is deleted.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $activity_ids_deleted IDs of deleted activity items.
 */
function bp_activity_at_mention_delete_notification( $activity_ids_deleted = array() ) {
	// Let's delete all without checking if content contains any mentions
	// to avoid a query to get the activity.
	if ( ! empty( $activity_ids_deleted ) ) {
		foreach ( $activity_ids_deleted as $activity_id ) {
			bp_notifications_delete_all_notifications_by_type( $activity_id, buddypress()->activity->id );
		}
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_activity_at_mention_delete_notification', 10 );

/**
 * Add a notification for post comments to the post author or post commenter.
 *
 * Requires "activity feed commenting on posts and comments" to be enabled.
 *
 * @since BuddyPress 2.6.0
 *
 * @param int        $activity_id          The activity comment ID.
 * @param WP_Comment $post_type_comment    WP Comment object.
 * @param array      $activity_args        Activity comment arguments.
 * @param object     $activity_post_object The post type tracking args object.
 */
function bp_activity_add_notification_for_synced_blog_comment( $activity_id, $post_type_comment, $activity_args, $activity_post_object ) {
	// If activity comments are disabled for WP posts, stop now!
	if (
		empty( $post_type_comment->post ) ||
		empty( $post_type_comment->post->post_type ) ||
		! bb_is_post_type_feed_comment_enable( $post_type_comment->post->post_type ) ||
		empty( $activity_id )
	) {
		return;
	}

	// Send a notification to the blog post author.
	if ( (int) $post_type_comment->post->post_author !== (int) $activity_args['user_id'] ) {
		// Only add a notification if comment author is a registered user.
		// @todo Should we remove this restriction?
		if ( ! empty( $post_type_comment->user_id ) ) {
			if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $post_type_comment->post->post_author, $post_type_comment->user_id ) ) {
				return;
			}

			$component_action = 'update_reply';
			if ( ! bb_enabled_legacy_email_preference() ) {
				$component_action = 'bb_activity_comment';
			}

			add_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );

			if ( ! empty( $post_type_comment->comment_parent ) ) {
				$parent_comment = get_comment( $post_type_comment->comment_parent );
				if ( ! empty( $parent_comment->user_id ) && (int) $parent_comment->user_id !== (int) $post_type_comment->post->post_author ) {
					bp_notifications_add_notification(
						array(
							'user_id'           => $post_type_comment->post->post_author,
							'item_id'           => $activity_id,
							'secondary_item_id' => $post_type_comment->user_id,
							'component_name'    => buddypress()->activity->id,
							'component_action'  => $component_action,
							'date_notified'     => $post_type_comment->comment_date_gmt,
							'is_new'            => 1,
						)
					);
				}
			} else {
				bp_notifications_add_notification(
					array(
						'user_id'           => $post_type_comment->post->post_author,
						'item_id'           => $activity_id,
						'secondary_item_id' => $post_type_comment->user_id,
						'component_name'    => buddypress()->activity->id,
						'component_action'  => $component_action,
						'date_notified'     => $post_type_comment->comment_date_gmt,
						'is_new'            => 1,
					)
				);
			}

			remove_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
		}
	}
}
add_action( 'bp_blogs_comment_sync_activity_comment', 'bp_activity_add_notification_for_synced_blog_comment', 20, 4 );

/**
 * Mark notifications as read when a user visits an single post.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_activity_remove_screen_notifications_single_post() {

	$reply_id         = filter_input( INPUT_GET, 'rid', FILTER_VALIDATE_INT );
	$comment_reply_id = filter_input( INPUT_GET, 'crid', FILTER_VALIDATE_INT );

	if (
		! is_single()
		|| (
			empty( $reply_id )
			&& empty( $comment_reply_id )
		)
	) {
		return;
	}

	$comment_id = 0;
	// For replies to a parent update.
	if ( ! empty( $reply_id ) ) {
		$comment_id = $reply_id;

		// For replies to an activity comment.
	} elseif ( ! empty( $comment_reply_id ) ) {
		$comment_id = (int) $comment_reply_id;
	}

	// Mark individual activity reply notification as read.
	if ( ! empty( $comment_id ) ) {
		$updated = BP_Notifications_Notification::update(
			array(
				'is_new' => false,
			),
			array(
				'user_id'        => bp_loggedin_user_id(),
				'id'             => $comment_id,
				'component_name' => 'activity',
			)
		);

		if ( 1 === $updated ) {
			$notifications_data = bp_notifications_get_notification( $comment_id );
			if ( isset( $notifications_data->item_id ) ) {
				BP_Notifications_Notification::update(
					array(
						'is_new' => false,
					),
					array(
						'user_id'        => bp_loggedin_user_id(),
						'item_id'        => $notifications_data->item_id,
						'component_name' => $notifications_data->component_name,
					)
				);

				if ( 'activity' === $notifications_data->component_name ) {
					$activity = new BP_Activity_Activity( $notifications_data->item_id );
					$post_id  = 0;
					if ( 'activity_comment' === $activity->type && $activity->item_id && $activity->item_id > 0 ) {
						// Get activity object.
						$comment_activity = new BP_Activity_Activity( $activity->item_id );
						if ( 'blogs' === $comment_activity->component && isset( $comment_activity->secondary_item_id ) && 'new_blog_' . get_post_type( $comment_activity->secondary_item_id ) === $comment_activity->type ) {
							$comment_post_type = $comment_activity->secondary_item_id;
							$get_post_type     = get_post_type( $comment_post_type );
							$post_id           = bp_activity_get_meta( $activity->id, 'bp_blogs_' . $get_post_type . '_comment_id', true );
						}

						if ( ! empty( $post_id ) ) {
							BP_Notifications_Notification::update(
								array(
									'is_new' => false,
								),
								array(
									'user_id'        => bp_loggedin_user_id(),
									'item_id'        => $post_id,
									'component_name' => 'core',
								)
							);
						}
					}
				}
			}
		}
	}
}
add_action( 'template_redirect', 'bp_activity_remove_screen_notifications_single_post' );

/**
 * Create notification meta based on activity.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param object $notification Notification object.
 */
function bb_activity_add_notification_metas( $notification ) {

	if (
		bb_enabled_legacy_email_preference() ||
		empty( $notification->id ) ||
		empty( $notification->item_id ) ||
		empty( $notification->component_action ) ||
		! in_array( $notification->component_action, array( 'bb_new_mention', 'bb_activity_comment' ), true )
	) {
		return;
	}

	$activity_id = $notification->item_id;
	$activity    = new BP_Activity_Activity( $activity_id );

	if ( empty( $activity->id ) ) {
		return;
	}

	if ( 'activity_comment' === $activity->type ) {
		if ( ! empty( $activity->item_id ) ) {
			$parent_activity = new BP_Activity_Activity( $activity->item_id );
			if ( ! empty( $parent_activity ) && 'blogs' === $parent_activity->component ) {
				bp_notifications_update_meta( $notification->id, 'type', 'post_comment' );
			} elseif ( ! empty( $parent_activity ) && 'activity_update' === $parent_activity->type && $activity->item_id === $activity->secondary_item_id ) {
				bp_notifications_update_meta( $notification->id, 'type', 'activity_post' );
			} else {
				bp_notifications_update_meta( $notification->id, 'type', 'activity_comment' );
			}
		} else {
			bp_notifications_update_meta( $notification->id, 'type', 'activity_comment' );
		}
	} elseif ( 'blogs' === $activity->component ) {
		bp_notifications_update_meta( $notification->id, 'type', 'post_comment' );
	} else {
		bp_notifications_update_meta( $notification->id, 'type', 'activity_post' );
	}
}

/**
 * Function will remove follow notification when a member withdraws their following.
 *
 * @since BuddyBoss 2.3.80
 *
 * @param BP_Activity_Follow $follower Contains following data.
 */
function bb_activity_follow_withdraw_notifications( $follower ) {

	if ( empty( $follower ) || ! bp_is_activity_follow_active() || empty( $follower->leader_id ) ) {
		return;
	}

	bp_notifications_delete_notifications_by_item_id(
		$follower->leader_id, // Following user id.
		$follower->id,
		buddypress()->activity->id,
		'bb_following_new',
		$follower->follower_id // Current user id.
	);
}
add_action( 'bp_stop_following', 'bb_activity_follow_withdraw_notifications', 10, 1 );
