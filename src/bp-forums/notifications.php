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

	$group    = false;
	$forum_id = bbp_get_forum_id( $forum_id );
	if ( bp_is_active( 'groups' ) && bb_is_forum_group_forum( $forum_id ) ) {
		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ! empty( $group_ids ) ? (int) current( $group_ids ) : 0;
		$group     = ! empty( $group_id ) ? groups_get_group( $group_id ) : false;
		if (
			! empty( $group->id ) &&
			'public' == bp_get_group_status( $group )
		) {
			$group = false;
		}
	}

	// Notify the topic author if not the current reply author.
	if ( $author_id !== $topic_author_id && $topic_author_id !== $reply_to_item_id ) {
		if (
			false === (bool) apply_filters( 'bb_is_recipient_moderated', false, $topic_author_id, $author_id ) &&
			(
				empty( $group ) ||
				groups_is_user_member( $topic_author_id, $group->id )
			)
		) {
			$args['secondary_item_id'] = $secondary_item_id;
			add_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
			bp_notifications_add_notification( $args );
			remove_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
		}
	}

	// Notify the immediate reply author if not the current reply author.
	if ( ! empty( $reply_to ) && ( $author_id !== $reply_to_item_id ) ) {
		if (
			false === (bool) apply_filters( 'bb_is_recipient_moderated', false, $reply_to_item_id, $author_id ) &&
			(
				empty( $group ) ||
				groups_is_user_member( $reply_to_item_id, $group->id )
			)
		) {
			$args['user_id']           = $reply_to_item_id;
			$args['secondary_item_id'] = $secondary_item_id;
			add_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
			bp_notifications_add_notification( $args );
			remove_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
		}
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

			// Moderated member found then prevent to send email/notifications.
			if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, get_current_user_id() ) ) {
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

				// Remove filters from reply content and topic title to prevent content
				// from being encoded with HTML entities, wrapped in paragraph tags, etc...
				remove_all_filters( 'bbp_get_reply_content' );
				remove_all_filters( 'bbp_get_topic_title' );

				// Strip tags from text and setup mail data.
				$reply_content = bbp_kses_data( bbp_get_reply_content( $reply_id ) );
				$reply_url     = bbp_get_reply_url( $reply_id );
				$title_text    = bbp_get_topic_title( $topic_id );

				// Check if link embed or link preview and append the content accordingly.
				if ( bbp_use_autoembed() ) {
					$link_embed = get_post_meta( $reply_id, '_link_embed', true );
					if ( empty( preg_replace( '/(?:<p>\s*<\/p>\s*)+|<p>(\s|(?:<br>|<\/br>|<br\/?>))*<\/p>/', '', $reply_content ) ) && ! empty( $link_embed ) ) {
						$reply_content .= bbp_make_clickable( $link_embed );
					} else {
						$reply_content = bb_forums_link_preview( $reply_content, $reply_id );
					}
				}

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
						'forum_id'          => $forum_id,
						'topic_id'          => $topic_id,
						'reply_id'          => $reply_id,
					),
				);

				bp_send_email( $email_type, $user_id, $args );
			}
		}
	}
}
add_action( 'bbp_new_reply', 'bbp_buddypress_add_notification', 9999, 7 );

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

			// Moderated member found then prevent to send email/notifications.
			if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, get_current_user_id() ) ) {
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

				// Remove filters from reply content and topic title to prevent content
				// from being encoded with HTML entities, wrapped in paragraph tags, etc...
				remove_all_filters( 'bbp_get_topic_content' );
				remove_all_filters( 'bbp_get_topic_title' );

				// Strip tags from text and setup mail data.
				$topic_content = bbp_kses_data( bbp_get_topic_content( $topic_id ) );
				$topic_url     = bbp_get_topic_permalink( $topic_id );
				$author_id     = bbp_get_topic_author_id( $topic_id );
				$title_text    = bbp_get_topic_title( $topic_id );

				// Check if link embed or link preview and append the content accordingly.
				if ( bbp_use_autoembed() ) {
					$link_embed = get_post_meta( $topic_id, '_link_embed', true );
					if ( empty( preg_replace( '/(?:<p>\s*<\/p>\s*)+|<p>(\s|(?:<br>|<\/br>|<br\/?>))*<\/p>/', '', $topic_content ) ) && ! empty( $link_embed ) ) {
						$topic_content .= bbp_make_clickable( $link_embed );
					} else {
						$topic_content = bb_forums_link_preview( $topic_content, $topic_id );
					}
				}

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
						'forum_id'          => $forum_id,
						'topic_id'          => $topic_id,
					),
				);

				bp_send_email( $email_type, $user_id, $args );
			}
		}
	}
}
add_action( 'bbp_new_topic', 'bbp_buddypress_add_topic_notification', 9999, 2 );

/**
 * Mark notifications as read when reading a topic
 *
 * @since bbPress (r5155)
 *
 * @param string $action Action name.
 *
 * @return void If not trying to mark a notification as read
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
	$reply_id = isset( $_GET['reply_id'] ) ? intval( $_GET['reply_id'] ) : 0;

	// Check nonce.
	if ( ! bbp_verify_nonce_request( 'bbp_mark_topic_' . $topic_id ) ) {
		bbp_add_error( 'bbp_notification_topic_id', __( '<strong>ERROR</strong>: Are you sure you wanted to do that?', 'buddyboss' ) );

		// Check current user's ability to edit the user.
	} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
		bbp_add_error( 'bbp_notification_permissions', __( '<strong>ERROR</strong>: You do not have permission to mark notifications for that user.', 'buddyboss' ) );
	}

	// Bail if we have errors.
	if ( ! bbp_has_errors() ) {

		if ( ! empty( $topic_id ) ) {
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $topic_id, bbp_get_component_name(), 'bbp_new_topic' );

			if ( bp_is_active( 'groups' ) ) {
				$success = bp_notifications_mark_notifications_by_item_id( $user_id, $topic_id, buddypress()->groups->id, 'bb_groups_subscribed_discussion' );
			}
		}

		if ( ! empty( $reply_id ) ) {
			// Attempt to clear notifications for the current user from this reply.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $reply_id, bbp_get_component_name(), 'bbp_new_reply' );
			// Clear mentions notifications by default.
			bp_notifications_mark_notifications_by_item_id( $user_id, $reply_id, bbp_get_component_name(), 'bbp_new_at_mention' );
		} else {
			// Attempt to clear notifications for the current user from this topic.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $topic_id, bbp_get_component_name(), 'bbp_new_reply' );
			// Clear mentions notifications by default.
			bp_notifications_mark_notifications_by_item_id( $user_id, $topic_id, bbp_get_component_name(), 'bbp_new_at_mention' );
		}

		// Do additional subscriptions actions.
		do_action( 'bbp_notifications_handler', $success, $user_id, $topic_id, $action );
	}

	if ( ! empty( $reply_id ) && 'reply' === get_post_type( $reply_id ) ) {
		// Redirect to the reply.
		$redirect = bbp_get_reply_url( $reply_id );
	} else {
		// Redirect to the topic.
		$redirect = bbp_get_reply_url( $topic_id );
	}

	// Redirect.
	bbp_redirect( $redirect );
}
add_action( 'bbp_get_request', 'bbp_buddypress_mark_notifications', 1 );

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
		bp_notifications_mark_notifications_by_item_id( $user_id, intval( $topic_id ), bbp_get_component_name(), 'bb_new_mention' );
		bp_notifications_mark_notifications_by_item_id( $user_id, intval( $topic_id ), bbp_get_component_name(), 'bb_forums_subscribed_discussion' );
	}

	if ( ! empty( $_GET['reply_id'] ) ) {
		bp_notifications_mark_notifications_by_item_id( $user_id, intval( $_GET['reply_id'] ), bbp_get_component_name(), 'bb_new_mention' );
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


/**
 * Mark notifications as read when a user visits an particular discussion/reply.
 *
 * @since BuddyBoss 2.3.0
 */
function bb_forums_remove_screen_notifications() {
	$reply_id = filter_input( INPUT_GET, 'rid', FILTER_VALIDATE_INT );

	if ( empty( $reply_id ) ) {
		return;
	}

	$comment_id = 0;
	// For replies.
	if ( ! empty( $reply_id ) ) {
		$comment_id = $reply_id;

	}

	// Mark individual activity reply notification as read.
	if ( ! empty( $comment_id ) ) {
		$success = BP_Notifications_Notification::update(
			array(
				'is_new' => false,
			),
			array(
				'user_id'          => bp_loggedin_user_id(),
				'id'               => $comment_id,
				'component_name'   => 'forums',
				'component_action' => 'bb_new_mention',
			)
		);
		if ( 1 === $success ) {
			$notifications_data = bp_notifications_get_notification( $comment_id );
			if ( isset( $notifications_data->item_id ) ) {
				$component_name   = 'forums';
				$component_action = 'bb_forums_subscribed_discussion';
				if ( function_exists( 'bp_is_group_forum_topic' ) && bp_is_group_forum_topic() ) {
					$component_name   = 'groups';
					$component_action = 'bb_groups_subscribed_discussion';
				}
				BP_Notifications_Notification::update(
					array(
						'is_new' => false,
					),
					array(
						'user_id'          => bp_loggedin_user_id(),
						'item_id'          => $notifications_data->item_id,
						'component_name'   => 'forums',
						'component_action' => 'bb_forums_subscribed_reply',
					)
				);
				BP_Notifications_Notification::update(
					array(
						'is_new' => false,
					),
					array(
						'user_id'          => bp_loggedin_user_id(),
						'item_id'          => $notifications_data->item_id,
						'component_name'   => $component_name,
						'component_action' => $component_action,
					)
				);
				if ( (int) $notifications_data->user_id === (int) bp_loggedin_user_id() ) {
					BP_Notifications_Notification::update(
						array(
							'is_new' => false,
						),
						array(
							'user_id'          => bp_loggedin_user_id(),
							'item_id'          => $notifications_data->item_id,
							'component_name'   => 'forums',
							'component_action' => 'bbp_new_reply',
						)
					);
				}
			}
		}
	}

}
add_action( 'template_redirect', 'bb_forums_remove_screen_notifications' );

/**
 * Delete forum reply notification once delete forum reply.
 *
 * @since BuddyBoss 2.4.20
 *
 * @param WP_Post $post_data Forum's reply post data.
 *
 * @return void
 */
function bb_delete_forum_topic_reply_notification( $post_data ) {
	if ( empty( $post_data ) ) {
		return;
	}

	if ( 'trash' === $post_data->post_status ) {
		if ( bbp_get_reply_post_type() === $post_data->post_type ) {
			bp_notifications_delete_all_notifications_by_type( $post_data->ID, 'forums', 'bb_forums_subscribed_reply' );
		}
		if ( bbp_get_topic_post_type() === $post_data->post_type ) {
			bp_notifications_delete_all_notifications_by_type( $post_data->ID, 'forums', 'bb_forums_subscribed_discussion' );
			bp_notifications_delete_all_notifications_by_type( $post_data->ID, 'groups', 'bb_groups_subscribed_discussion' );
		}
	}
}
add_action( 'bbp_toggle_reply_handler', 'bb_delete_forum_topic_reply_notification', 1 );
add_action( 'bbp_toggle_topic_handler', 'bb_delete_forum_topic_reply_notification', 1 );
