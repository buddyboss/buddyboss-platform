<?php

/**
 * Filter registered notifications components, and add 'forums' to the queried
 * 'component_name' array.
 *
 * @since bbPress (r5232)
 *
 * @see BP_Notifications_Notification::get()
 * @param array $component_names
 * @return array
 */
function bbp_filter_notifications_get_registered_components( $component_names = array() ) {

	// Force $component_names to be an array
	if ( ! is_array( $component_names ) ) {
		$component_names = array();
	}

	// Add 'forums' component to registered components array
	array_push( $component_names, bbp_get_component_name() );

	// Return component's with 'forums' appended
	return $component_names;
}
add_filter( 'bp_notifications_get_registered_components', 'bbp_filter_notifications_get_registered_components', 10 );

/**
 * Format the BuddyBar/Toolbar notifications
 *
 * @since   bbPress (r5155)
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item id.
 * @param int    $secondary_item_id The secondary item id.
 * @param int    $total_items       The total number of messaging-related notifications waiting for the user.
 * @param string $format            'string' for BuddyBar-compatible notifications; 'array' for WP Toolbar.
 * @param string $action_name       Canonical notification action.
 * @param string $name              Notification component ID.
 * @param int    $id                Notification ID.
 * @param string $screen            Notification Screen type.
 *
 * @package BuddyBoss
 */
function bbp_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $action_name = '', $name = '', $id = 0, $screen = 'web' ) {

	// New reply notifications.
	if ( 'bbp_new_reply' === $action_name ) {
		$topic_id    = bbp_get_reply_topic_id( $item_id );
		$topic_title = bbp_get_topic_title( $topic_id );
		$topic_link  = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'bbp_mark_read',
					'topic_id' => $topic_id,
					'reply_id' => $item_id,
				),
				bbp_get_reply_url( $item_id )
			),
			'bbp_mark_topic_' . $topic_id
		);
		$title_attr  = __( 'Discussion Replies', 'buddyboss' );

		if ( (int) $total_items > 1 ) {
			$text   = sprintf( __( 'You have %d new replies', 'buddyboss' ), (int) $total_items );
			$filter = 'bbp_multiple_new_subscription_notification';
		} else {
			if ( ! empty( $secondary_item_id ) ) {
				$text = sprintf( __( 'You have %d new reply to %2$s from %3$s', 'buddyboss' ), (int) $total_items, $topic_title, bp_core_get_user_displayname( $secondary_item_id ) );
			} else {
				$text = sprintf( __( 'You have %1$d new reply to %2$s', 'buddyboss' ), (int) $total_items, $topic_title );
			}
			$filter = 'bbp_single_new_subscription_notification';
		}

		// WordPress Toolbar.
		if ( 'string' === $format ) {
			$return = apply_filters( $filter, '<a href="' . esc_url( $topic_link ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $topic_link );

			// Deprecated BuddyBar.
		} else {
			$return = apply_filters(
				$filter,
				array(
					'text' => $text,
					'link' => $topic_link,
				),
				$topic_link,
				(int) $total_items,
				$text,
				$topic_title
			);
		}

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bbp_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );

		return $return;
	}

	// New topic notifications.
	if ( 'bbp_new_topic' === $action_name ) {
		$topic_id    = bbp_get_topic_id( $item_id );
		$topic_title = bbp_get_topic_title( $topic_id );
		$topic_link  = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'bbp_mark_read',
					'topic_id' => $topic_id,
				),
				bbp_get_topic_permalink( $topic_id )
			),
			'bbp_mark_topic_' . $topic_id
		);

		$title_attr = esc_html__( 'Discussion started', 'buddyboss' );

		if ( (int) $total_items > 1 ) {
			$text   = sprintf( __( 'You have %d new discussion', 'buddyboss' ), (int) $total_items );
			$filter = 'bbp_multiple_new_discussion_subscription_notification';
		} else {

			if ( ! empty( $secondary_item_id ) ) {
				$text = sprintf( __( '%1$s started a discussion: "%2$s"', 'buddyboss' ), bp_core_get_user_displayname( $secondary_item_id ), $topic_title );
			} else {
				$text = sprintf( __( 'You have a new discussion: "%s"', 'buddyboss' ), $topic_title );
			}

			$filter = 'bbp_single_new_discussion_subscription_notification';
		}

		// WordPress Toolbar.
		if ( 'string' === $format ) {
			$return = apply_filters( $filter, '<a href="' . esc_url( $topic_link ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $topic_link );

			// Deprecated BuddyBar.
		} else {
			$return = apply_filters(
				$filter,
				array(
					'text' => $text,
					'link' => $topic_link,
				),
				$topic_link,
				(int) $total_items,
				$text,
				$topic_title
			);
		}

		/**
		 * Format notification.
		 *
		 * @since BuddyBoss 1.9.3
		 */
		do_action( 'bbp_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );

		return $return;
	}

	if ( 'bbp_new_at_mention' === $action_name ) {
		$topic_id = bbp_get_reply_topic_id( $item_id );

		if ( empty( $topic_id ) ) {
			$topic_id = $item_id;
		}

		$topic_title = bbp_get_topic_title( $topic_id );
		$topic_link  = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'bbp_mark_read',
					'topic_id' => $topic_id,
					'reply_id' => $item_id,
				),
				bbp_get_reply_url( $item_id )
			),
			'bbp_mark_topic_' . $topic_id
		);
		$title_attr  = __( 'Discussion Mentions', 'buddyboss' );

		if ( (int) $total_items > 1 ) {
			$text   = sprintf( __( 'You have %d new mentions', 'buddyboss' ), (int) $total_items );
			$filter = 'bbp_multiple_new_subscription_notification';
		} else {
			if ( ! empty( $secondary_item_id ) ) {
				$text = sprintf( __( '%3$s mentioned you in %2$s', 'buddyboss' ), (int) $total_items, $topic_title, bp_core_get_user_displayname( $secondary_item_id ) );
			} else {
				$text = sprintf( __( 'You have %1$d new mention to %2$s', 'buddyboss' ), (int) $total_items, $topic_title );
			}
			$filter = 'bbp_single_new_subscription_notification';
		}

		// WordPress Toolbar.
		if ( 'string' === $format ) {
			$return = apply_filters( $filter, '<a href="' . esc_url( $topic_link ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $topic_link );

			// Deprecated BuddyBar.
		} else {
			$return = apply_filters(
				$filter,
				array(
					'text' => $text,
					'link' => $topic_link,
				),
				$topic_link,
				(int) $total_items,
				$text,
				$topic_title
			);
		}

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bbp_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );

		return $return;
	}

	if ( 'forums' === $name && ! empty( $action_name ) ) {

		/**
		 * Filters plugin-added forum-related custom component_actions.
		 *
		 * @since BuddyBoss 1.9.3
		 *
		 * @param string $notification      Null value.
		 * @param int    $item_id           The primary item id.
		 * @param int    $secondary_item_id The secondary item id.
		 * @param int    $total_items       The total number of messaging-related notifications waiting for the user.
		 * @param string $format            'string' for BuddyBar-compatible notifications; 'array' for WP Toolbar.
		 * @param string $action_name       Canonical notification action.
		 * @param string $name              Notification component ID.
		 * @param int    $id                Notification ID.
		 * @param string $screen            Notification Screen type.
		 */
		$custom_action_notification = apply_filters( 'bp_forums_' . $action_name . '_notification', null, $item_id, $secondary_item_id, $total_items, $format, $id, $screen );

		if ( ! is_null( $custom_action_notification ) ) {
			return $custom_action_notification;
		}
	}

	return $action;
}
add_filter( 'bp_notifications_get_notifications_for_user', 'bbp_format_buddypress_notifications', 10, 9 );

/**
 * Hooked into the new reply function, this notification action is responsible
 * for notifying topic and hierarchical reply authors of topic replies.
 *
 * @since bbPress (r5156)
 *
 * @param int   $reply_id Reply id.
 * @param int   $topic_id Topic id.
 * @param int   $forum_id (not used) Forum id.
 * @param array $anonymous_data (not used) Anonymous data.
 * @param int   $author_id Author id.
 * @param bool  $is_edit Used to bail if this gets hooked to an edit action.
 * @param int   $reply_to Reply to id.
 */
function bbp_buddypress_add_notification( $reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $author_id = 0, $is_edit = false, $reply_to = 0 ) {

	// Bail if somehow this is hooked to an edit action.
	if ( ! empty( $is_edit ) ) {
		return;
	}

	// Define variable.
	$reply_to_item_id = 0;

	// Get author information.
	$topic_author_id   = bbp_get_topic_author_id( $topic_id );
	$secondary_item_id = $author_id;

	// Hierarchical replies.
	if ( ! empty( $reply_to ) ) {
		$reply_to_item_id = bbp_get_topic_author_id( $reply_to );
	}

	// Get some reply information.
	$args = array(
		'user_id'          => $topic_author_id,
		'item_id'          => $reply_id,
		'component_name'   => bbp_get_component_name(),
		'component_action' => 'bbp_new_reply',
		'date_notified'    => get_post( $reply_id )->post_date_gmt,
	);

	// Notify the topic author if not the current reply author.
	if ( $author_id !== $topic_author_id && $topic_author_id !== $reply_to_item_id ) {
		$args['secondary_item_id'] = $secondary_item_id;

		bp_notifications_add_notification( $args );
	}

	// Notify the immediate reply author if not the current reply author.
	if ( ! empty( $reply_to ) && ( $author_id !== $reply_to_item_id ) ) {
		$args['user_id']           = $reply_to_item_id;
		$args['secondary_item_id'] = $secondary_item_id;

		bp_notifications_add_notification( $args );
	}

	// If our temporary variable doesn't exist, stop now.
	if ( empty( buddypress()->forums->mentioned_users ) ) {
		return;
	}

	// Grab our temporary variable from bbp_convert_mentions().
	$usernames = buddypress()->forums->mentioned_users;

	// Get rid of temporary variable.
	unset( buddypress()->forums->mentioned_users );

	// We have mentions!
	if ( ! empty( $usernames ) ) {

		unset( $args['date_notified'] ); // use BP default timestamp.

		// Send @mentions and setup BP notifications.
		foreach ( (array) $usernames as $user_id => $username ) {

			// Current user is the same user then do not send notification.
			if ( get_current_user_id() === $user_id ) {
				continue;
			}

			$args['user_id']           = $user_id;
			$args['component_action']  = bb_enabled_legacy_email_preference() ? 'bbp_new_at_mention' : 'bb_new_mention';
			$args['secondary_item_id'] = get_current_user_id();

			// If forum is not accesible to user, do not send notification.
			$can_access = bbp_user_can_view_forum(
				array(
					'user_id'         => $user_id,
					'forum_id'        => $forum_id,
					'check_ancestors' => true,
				)
			);

			/**
			 * Filters bbPress' ability to send notifications for @mentions.
			 *
			 * @param bool $value Whether or not BuddyPress should send a notification to the mentioned users.
			 * @param array $usernames Array of users potentially notified.
			 * @param int $user_id ID of the current user being notified.
			 * @param int $forum_id ID of forum.
			 *
			 * @since BuddyBoss 1.2.9
			 */
			if ( ! apply_filters( 'bbp_forums_at_name_do_notifications', $can_access, $usernames, $user_id, $forum_id ) ) {
				continue;
			}

			add_action( 'bp_notification_after_save', 'bb_forums_add_notification_metas', 5 );

			bp_notifications_add_notification( $args );

			remove_action( 'bp_notification_after_save', 'bb_forums_add_notification_metas', 5 );

			// User Mentions email.
			if ( ! bb_enabled_legacy_email_preference() && true === bb_is_notification_enabled( $user_id, 'bb_new_mention' ) ) {

				// Check the sender is blocked by recipient or not.
				if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, get_current_user_id() ) ) {
					continue;
				}

				$reply_id = bbp_get_reply_id( $reply_id );
				$topic_id = bbp_get_topic_id( $topic_id );
				$forum_id = bbp_get_forum_id( $forum_id );

				// Poster name.
				$reply_author_name = bbp_get_reply_author_display_name( $reply_id );
				$author_id         = bbp_get_reply_author_id( $reply_id );

				/** Mail */

				// Strip tags from text and setup mail data.
				$reply_content = bbp_kses_data( bbp_get_reply_content( $reply_id ) );
				$reply_url     = bbp_get_reply_url( $reply_id );
				$title_text    = bbp_get_topic_title( $topic_id );

				$group_ids  = bbp_get_forum_group_ids( $forum_id );
				$group_id   = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
				$group_name = '';

				if ( $group_id && bp_is_active( 'groups' ) ) {
					$email_type = 'new-mention-group';
					$group      = groups_get_group( $group_id );
					$group_name = bp_get_group_name( $group );
				} else {
					$email_type = 'new-mention';
				}

				$unsubscribe_args = array(
					'user_id'           => $user_id,
					'notification_type' => $email_type,
				);

				$notification_type_html = esc_html__( 'discussion', 'buddyboss' );

				$args = array(
					'tokens' => array(
						'usermessage'       => wp_strip_all_tags( $reply_content ),
						'mentioned.url'     => $reply_url,
						'group.name'        => $group_name,
						'poster.name'       => $reply_author_name,
						'receiver-user.id'  => $user_id,
						'unsubscribe'       => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
						'mentioned.type'    => $notification_type_html,
						'mentioned.content' => $reply_content,
						'author_id'         => $author_id,
						'reply_text'        => esc_html__( 'View Reply', 'buddyboss' ),
						'title_text'        => $title_text,
					),
				);

				bp_send_email( $email_type, $user_id, $args );
			}
		}
	}
}
add_action( 'bbp_new_reply', 'bbp_buddypress_add_notification', 10, 7 );

/**
 * Hooked into the new topic function, this notification action is responsible
 * for notifying topic.
 *
 * @since BuddyBoss 1.2.8
 *
 * @param int $topic_id
 * @param int $forum_id
 */
function bbp_buddypress_add_topic_notification( $topic_id, $forum_id ) {
	// If our temporary variable doesn't exist, stop now.
	if ( empty( buddypress()->forums->mentioned_users ) ) {
		return;
	}

	// Get some topic information.
	$args = array(
		'item_id'           => $topic_id,
		'secondary_item_id' => get_current_user_id(),
		'component_name'    => bbp_get_component_name(),
		'component_action'  => bb_enabled_legacy_email_preference() ? 'bbp_new_at_mention' : 'bb_new_mention',
	);

	// Grab our temporary variable from bbp_convert_mentions().
	$usernames = buddypress()->forums->mentioned_users;

	// Get rid of temporary variable.
	unset( buddypress()->forums->mentioned_users );

	// We have mentions!
	if ( ! empty( $usernames ) ) {

		// Send @mentions and setup BP notifications.
		foreach ( (array) $usernames as $user_id => $username ) {

			// Current user is the same user then do not send notification.
			if ( get_current_user_id() === $user_id ) {
				continue;
			}

			$args['user_id'] = $user_id;

			// If forum is not accessible to user, do not send notification.
			$can_access = bbp_user_can_view_forum(
				array(
					'user_id'         => $user_id,
					'forum_id'        => $forum_id,
					'check_ancestors' => true,
				)
			);

			/**
			 * Filters bbPress' ability to send notifications for @mentions.
			 *
			 * @param bool $value Whether or not BuddyPress should send a notification to the mentioned users.
			 * @param array $usernames Array of users potentially notified.
			 * @param int $user_id ID of the current user being notified.
			 * @param int $forum_id ID of forum.
			 *
			 * @since BuddyBoss 1.2.9
			 */
			if ( ! apply_filters( 'bbp_forums_at_name_do_notifications', $can_access, $usernames, $user_id, $forum_id ) ) {
				continue;
			}

			add_action( 'bp_notification_after_save', 'bb_forums_add_notification_metas', 5 );

			bp_notifications_add_notification( $args );

			remove_action( 'bp_notification_after_save', 'bb_forums_add_notification_metas', 5 );

			// User Mentions email.
			if ( ! bb_enabled_legacy_email_preference() && true === bb_is_notification_enabled( $user_id, 'bb_new_mention' ) ) {

				// Check the sender is blocked by recipient or not.
				if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, get_current_user_id() ) ) {
					continue;
				}

				$topic_id = bbp_get_topic_id( $topic_id );
				$forum_id = bbp_get_forum_id( $forum_id );

				// Poster name.
				$reply_author_name = bbp_get_reply_author_display_name( $topic_id );

				/** Mail */

				// Strip tags from text and setup mail data.
				$topic_content = bbp_kses_data( bbp_get_topic_content( $topic_id ) );
				$topic_url     = bbp_get_topic_permalink( $topic_id );
				$author_id     = bbp_get_topic_author_id( $topic_id );
				$title_text    = bbp_get_topic_title( $topic_id );

				$group_ids  = bbp_get_forum_group_ids( $forum_id );
				$group_id   = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
				$group_name = '';

				if ( $group_id && bp_is_active( 'groups' ) ) {
					$email_type = 'new-mention-group';
					$group      = groups_get_group( $group_id );
					$group_name = bp_get_group_name( $group );
				} else {
					$email_type = 'new-mention';
				}

				$unsubscribe_args = array(
					'user_id'           => $user_id,
					'notification_type' => $email_type,
				);

				$notification_type_html = esc_html__( 'discussion', 'buddyboss' );

				$args = array(
					'tokens' => array(
						'usermessage'       => wp_strip_all_tags( $topic_content ),
						'mentioned.url'     => $topic_url,
						'group.name'        => $group_name,
						'poster.name'       => $reply_author_name,
						'receiver-user.id'  => $user_id,
						'unsubscribe'       => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
						'mentioned.type'    => $notification_type_html,
						'mentioned.content' => $topic_content,
						'author_id'         => $author_id,
						'reply_text'        => esc_html__( 'View Discussion', 'buddyboss' ),
						'title_text'        => $title_text,
					),
				);

				bp_send_email( $email_type, $user_id, $args );
			}
		}
	}
}
add_action( 'bbp_new_topic', 'bbp_buddypress_add_topic_notification', 10, 2 );

/**
 * Mark notifications as read when reading a topic
 *
 * @since bbPress (r5155)
 *
 * @return If not trying to mark a notification as read
 */
function bbp_buddypress_mark_notifications( $action = '' ) {

	// Bail if no topic ID is passed.
	if ( empty( $_GET['topic_id'] ) ) {
		return;
	}

	// Bail if action is not for this function.
	if ( 'bbp_mark_read' !== $action ) {
		return;
	}

	// Get required data.
	$user_id  = bp_loggedin_user_id();
	$topic_id = intval( $_GET['topic_id'] );

	// Check nonce.
	if ( ! bbp_verify_nonce_request( 'bbp_mark_topic_' . $topic_id ) ) {
		bbp_add_error( 'bbp_notification_topic_id', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'buddyboss' ) );

		// Check current user's ability to edit the user.
	} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
		bbp_add_error( 'bbp_notification_permissions', __( '<strong>ERROR</strong>: You do not have permission to mark notifications for that user.', 'buddyboss' ) );
	}

	// Bail if we have errors.
	if ( ! bbp_has_errors() ) {

		if ( ! empty( $_GET['topic_id'] ) ) {
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, intval( $_GET['topic_id'] ), bbp_get_component_name(), 'bbp_new_topic' );
		}

		if ( ! empty( $_GET['reply_id'] ) ) {
			// Attempt to clear notifications for the current user from this reply.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, intval( $_GET['reply_id'] ), bbp_get_component_name(), 'bbp_new_reply' );
			// Clear mentions notifications by default.
			bp_notifications_mark_notifications_by_item_id( $user_id, intval( $_GET['reply_id'] ), bbp_get_component_name(), 'bbp_new_at_mention' );
		} else {
			// Attempt to clear notifications for the current user from this topic.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $topic_id, bbp_get_component_name(), 'bbp_new_reply' );
			// Clear mentions notifications by default.
			bp_notifications_mark_notifications_by_item_id( $user_id, $topic_id, bbp_get_component_name(), 'bbp_new_at_mention' );
		}

		// Do additional subscriptions actions.
		do_action( 'bbp_notifications_handler', $success, $user_id, $topic_id, $action );
	}

	if ( ! empty( $_GET['reply_id'] ) && get_post_type( (int) $_GET['reply_id'] ) == 'reply' ) {
		// Redirect to the reply
		$redirect = bbp_get_reply_url( (int) $_GET['reply_id'] );
	} else {
		// Redirect to the topic
		$redirect = bbp_get_reply_url( $topic_id );
	}

	// Redirect
	wp_safe_redirect( $redirect );

	// For good measure
	exit();
}
add_action( 'bbp_get_request', 'bbp_buddypress_mark_notifications', 1 );

/**
 * Add notifications for the forum subscribers for creating a new discussion.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int   $topic_id Topic id.
 * @param int   $forum_id Forum id.
 * @param array $user_ids Array of users list.
 *
 * @return void
 */
function bb_pre_notify_forum_subscribers( $topic_id, $forum_id, $user_ids ) {
	if ( bb_enabled_legacy_email_preference() || ! bp_is_active( 'notifications' ) || empty( $user_ids ) ) {
		return;
	}

	$user_ids        = wp_parse_id_list( $user_ids );
	$topic_author_id = bbp_get_topic_author_id( $topic_id );

	// Remove Topic author from the users.
	$unset_topic_key = array_search( $topic_author_id, $user_ids, true );
	if ( false !== $unset_topic_key ) {
		unset( $user_ids[ $unset_topic_key ] );
	}

	if (
		function_exists( 'bb_notifications_background_enabled' ) &&
		true === bb_notifications_background_enabled() &&
		count( $user_ids ) > 20
	) {
		global $bb_notifications_background_updater;
		$bb_notifications_background_updater->data(
			array(
				array(
					'callback' => 'bb_add_background_notifications',
					'args'     => array(
						$user_ids,
						$topic_id,
						$topic_author_id,
						bbp_get_component_name(),
						'bb_forums_subscribed_discussion',
						bp_core_current_time(),
						true,
					),
				),
			)
		);
		$bb_notifications_background_updater->save()->dispatch();
	} else {
		foreach ( $user_ids as $user_id ) {
			bp_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $topic_id,
					'secondary_item_id' => $topic_author_id,
					'component_name'    => bbp_get_component_name(),
					'component_action'  => 'bb_forums_subscribed_discussion',
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
	}
}
add_action( 'bbp_pre_notify_forum_subscribers', 'bb_pre_notify_forum_subscribers', 10, 3 );

/**
 * Add notifications for the forum subscribers for creating a new discussion.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int   $reply_id Topic id.
 * @param int   $topic_id Forum id.
 * @param array $user_ids Array of users list.
 *
 * @return void
 */
function bb_pre_notify_reply_subscribers( $reply_id, $topic_id, $user_ids ) {
	if ( bb_enabled_legacy_email_preference() || ! bp_is_active( 'notifications' ) || empty( $user_ids ) ) {
		return;
	}

	$user_ids        = wp_parse_id_list( $user_ids );
	$reply_author_id = bbp_get_reply_author_id( $reply_id );
	$reply_to_id     = bbp_get_reply_to( $reply_id );

	// Remove Topic author from the users.
	$unset_reply_key = array_search( $reply_author_id, $user_ids, true );
	if ( false !== $unset_reply_key ) {
		unset( $user_ids[ $unset_reply_key ] );
	}

	if ( ! empty( $reply_to_id ) ) {
		$reply_to_author_id = bbp_get_reply_author_id( $reply_to_id );

		$unset_reply_to_key = array_search( $reply_to_author_id, $user_ids, true );
		if ( false !== $unset_reply_to_key ) {
			unset( $user_ids[ $unset_reply_to_key ] );
		}
	}

	$action = 'bb_forums_subscribed_reply';

	if (
		function_exists( 'bb_notifications_background_enabled' ) &&
		true === bb_notifications_background_enabled() &&
		count( $user_ids ) > 20
	) {
		global $bb_notifications_background_updater;
		$bb_notifications_background_updater->data(
			array(
				array(
					'callback' => 'bb_add_background_notifications',
					'args'     => array(
						$user_ids,
						$reply_id,
						$reply_author_id,
						bbp_get_component_name(),
						$action,
						bp_core_current_time(),
						true,
					),
				),
			)
		);
		$bb_notifications_background_updater->save()->dispatch();
	} else {
		foreach ( $user_ids as $user_id ) {
			bp_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $reply_id,
					'secondary_item_id' => $reply_author_id,
					'component_name'    => bbp_get_component_name(),
					'component_action'  => $action,
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
	}

}
add_action( 'bbp_pre_notify_subscribers', 'bb_pre_notify_reply_subscribers', 10, 3 );

/**
 * Mark notifications as read when reading a topic or reply subscribed notification.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param bool $success  any sucess ready performed or not.
 * @param int  $user_id  Current user ID.
 * @param int  $topic_id Topic ID.
 *
 * @return void
 */
function bb_mark_modern_notifications( $success, $user_id, $topic_id ) {

	if ( empty( $user_id ) ) {
		return;
	}

	if ( ! empty( $topic_id ) ) {
		bp_notifications_mark_notifications_by_item_id( $user_id, intval( $topic_id ), bbp_get_component_name(), 'bb_forums_subscribed_discussion' );
	}

	if ( ! empty( $_GET['reply_id'] ) ) {
		bp_notifications_mark_notifications_by_item_id( $user_id, intval( $_GET['reply_id'] ), bbp_get_component_name(), 'bb_forums_subscribed_reply' );
	}
}

add_action( 'bbp_notifications_handler', 'bb_mark_modern_notifications', 10, 3 );

/**
 * Create notification meta based on forums.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param object $notification Notification object.
 */
function bb_forums_add_notification_metas( $notification ) {
	if (
		bb_enabled_legacy_email_preference() ||
		empty( $notification->id ) ||
		empty( $notification->item_id ) ||
		empty( $notification->component_action ) ||
		'bb_new_mention' !== $notification->component_action
	) {
		return;
	}

	$reply_id = bbp_get_reply_id( $notification->item_id );
	$topic_id = bbp_get_topic_id( $notification->item_id );

	if ( bbp_is_reply( $reply_id ) ) {
		bp_notifications_update_meta( $notification->id, 'type', 'forum_reply' );
	} elseif ( bbp_is_topic( $topic_id ) ) {
		bp_notifications_update_meta( $notification->id, 'type', 'forum_topic' );
	}
}
