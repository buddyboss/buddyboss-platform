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
 * @return string $return Formatted @mention notification.
 */
function bp_activity_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $id = 0 ) {
	$action_filter = $action;
	$return        = false;
	$activity_id   = $item_id;
	$user_id       = $secondary_item_id;
	$user_fullname = bp_core_get_user_displayname( $user_id );

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
	bp_notifications_add_notification(
		array(
			'user_id'           => $receiver_user_id,
			'item_id'           => $activity->id,
			'secondary_item_id' => $activity->user_id,
			'component_name'    => buddypress()->activity->id,
			'component_action'  => 'new_at_mention',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
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
	bp_notifications_add_notification(
		array(
			'user_id'           => $activity->user_id,
			'item_id'           => $comment_id,
			'secondary_item_id' => $commenter_id,
			'component_name'    => buddypress()->activity->id,
			'component_action'  => 'update_reply',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
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
	bp_notifications_add_notification(
		array(
			'user_id'           => $activity_comment->user_id,
			'item_id'           => $comment_id,
			'secondary_item_id' => $commenter_id,
			'component_name'    => buddypress()->activity->id,
			'component_action'  => 'comment_reply',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
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
	if ( bp_disable_blogforum_comments() || empty( $activity_id ) ) {
		return;
	}

	// Send a notification to the blog post author.
	if ( (int) $post_type_comment->post->post_author !== (int) $activity_args['user_id'] ) {
		// Only add a notification if comment author is a registered user.
		// @todo Should we remove this restriction?
		if ( ! empty( $post_type_comment->user_id ) ) {
			if ( ! empty( $post_type_comment->comment_parent ) ) {
				$parent_comment = get_comment( $post_type_comment->comment_parent );
				if ( ! empty( $parent_comment->user_id ) && (int) $parent_comment->user_id !== (int) $post_type_comment->post->post_author ) {
					bp_notifications_add_notification(
						array(
							'user_id'           => $post_type_comment->post->post_author,
							'item_id'           => $activity_id,
							'secondary_item_id' => $post_type_comment->user_id,
							'component_name'    => buddypress()->activity->id,
							'component_action'  => 'update_reply',
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
						'component_action'  => 'update_reply',
						'date_notified'     => $post_type_comment->comment_date_gmt,
						'is_new'            => 1,
					)
				);
			}
		}
	}

	// Send a notification to the parent comment author for follow-up comments.
	if ( ! empty( $post_type_comment->comment_parent ) ) {
		$parent_comment = get_comment( $post_type_comment->comment_parent );

		if ( ! empty( $parent_comment->user_id ) && (int) $parent_comment->user_id !== (int) $activity_args['user_id'] ) {
			bp_notifications_add_notification(
				array(
					'user_id'           => $parent_comment->user_id,
					'item_id'           => $activity_id,
					'secondary_item_id' => $post_type_comment->user_id,
					'component_name'    => buddypress()->activity->id,
					'component_action'  => 'comment_reply',
					'date_notified'     => $post_type_comment->comment_date_gmt,
					'is_new'            => 1,
				)
			);
		}
	}
}
add_action( 'bp_blogs_comment_sync_activity_comment', 'bp_activity_add_notification_for_synced_blog_comment', 10, 4 );

/**
 * Add activity notifications settings to the notifications settings page.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_screen_notification_settings() {

	$options                  = bb_register_notification_preferences( buddypress()->activity->id );
	$enabled_all_notification = bp_get_option( 'bb_enabled_notification', array() );

	if ( empty( $options['fields'] ) ) {
		return;
	}

	$default_enabled_notifications = array_column( $options['fields'], 'default', 'key' );
	$enabled_notification          = array_filter( array_combine( array_keys( $enabled_all_notification ), array_column( $enabled_all_notification, 'main' ) ) );
	$enabled_notification          = array_merge( $default_enabled_notifications, $enabled_notification );

	$options['fields'] = array_filter(
		$options['fields'],
		function ( $var ) use ( $enabled_notification ) {
			return ( key_exists( $var['key'], $enabled_notification ) && 'yes' === $enabled_notification[ $var['key'] ] );
		}
	);

	if ( ! empty( $options['fields'] ) ) {
		?>

		<table class="main-notification-settings">
			<tbody>

			<?php if ( ! empty( $options['label'] ) ) { ?>
				<tr class="notification_heading">
					<td class="title" colspan="3"><?php echo esc_html( $options['label'] ); ?></td>
				</tr>
				<?php
			}

			foreach ( $options['fields'] as $field ) {

				$email_checked = bp_get_user_meta( bp_displayed_user_id(), $field['key'], true );
				$web_checked   = bp_get_user_meta( bp_displayed_user_id(), $field['key'] . '_web', true );
				$app_checked   = bp_get_user_meta( bp_displayed_user_id(), $field['key'] . '_app', true );

				if ( ! $email_checked ) {
					$email_checked = ( $enabled_all_notification[ $field['key'] ]['email'] ?? $field['default'] );
				}

				if ( ! $web_checked ) {
					$web_checked = ( $enabled_all_notification[ $field['key'] ]['web'] ?? $field['default'] );
				}

				if ( ! $app_checked ) {
					$app_checked = ( $enabled_all_notification[ $field['key'] ]['app'] ?? $field['default'] );
				}

				$options = apply_filters(
					'bb_notifications_types',
					array(
						'email' => array(
							'is_enabled' => true,
							'is_checked' => ( ! $email_checked ? $field['default'] : $email_checked ),
							'label'      => esc_html_x( 'Email', 'Notification preference label', 'buddyboss' ),
						),
						'web'   => array(
							'is_enabled' => true,
							'is_checked' => ( ! $web_checked ? $field['default'] : $web_checked ),
							'label'      => esc_html_x( 'Web', 'Notification preference label', 'buddyboss' ),
						),
						'app'   => array(
							'is_enabled' => true,
							'is_checked' => ( ! $app_checked ? $field['default'] : $app_checked ),
							'label'      => esc_html_x( 'App', 'Notification preference label', 'buddyboss' ),
						),
					)
				);

				?>
				<tr>
					<td><?php echo( isset( $field['label'] ) ? esc_html( $field['label'] ) : '' ); ?></td>

					<?php
					foreach ( $options as $key => $v ) {

						$is_disabled = apply_filters( 'bb_is_' . $field['key'] . $key . 'preference_enabled', false );
						$is_render   = apply_filters( 'bb_is_' . $field['key'] . $key . 'preference_type_render', true );
						if ( $is_render ) {
							?>
							<td class="<?php echo esc_attr( $key ); ?>">
								<input type="checkbox" id="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>" name="notifications[<?php echo esc_attr( $field['key'] ); ?>]" class="bs-styled-checkbox" value="yes" <?php checked( $v['is_checked'], 'yes' ); ?> />
								<label for="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>"><?php echo esc_html( $v['label'] ); ?></label>
							</td>
							<?php
						} else {
							?>
							<td class="<?php echo esc_attr( $key ); ?> notification_no_option">
								<?php esc_html_e( '-', 'buddyboss' ); ?>
							</td>
							<?php
						}
					}
					?>
				</tr>
				<?php
			}

			?>
			</tbody>
		</table>

		<?php
	}
}
add_action( 'bp_notification_settings', 'bp_activity_screen_notification_settings', 1 );

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
add_action( 'template_redirect', 'bp_activity_remove_screen_notifications_single_post' );

/**
 * Add Notifications for the activity.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $array Array of notifications.
 *
 * @return mixed
 */
function bb_activity_register_notifications( $array ) {
	$activity_notification = array(
		'label'  => esc_html__( 'Activity Feed', 'buddyboss' ),
		'fields' => array(),
	);

	if ( bp_activity_do_mentions() ) {
		$current_user                      = wp_get_current_user();
		$activity_notification['fields'][] = array(
			'key'         => 'notification_activity_new_mention',
			'admin_label' => esc_html__( 'A member is mentioned in another member’s update', 'buddyboss' ),
			'label'       => sprintf( __( 'A member mentions you in an update using "@%s"', 'buddyboss' ), bp_activity_get_user_mentionname( $current_user->ID ) ),
			'default'     => 'yes',
			'options'     => array(
				array(
					'name'  => esc_html__( 'Yes, send email', 'buddyboss' ),
					'value' => 'yes',
				),
				array(
					'name'  => esc_html__( 'No, do not send email', 'buddyboss' ),
					'value' => 'no',
				),
			),
		);
	}

	$activity_notification['fields'][] = array(
		'key'         => 'notification_activity_new_reply',
		'admin_label' => esc_html__( 'A member receives a reply to an update or comment they’ve posted', 'buddyboss' ),
		'label'       => esc_html__( 'A member replies to an update or comment you’ve posted', 'buddyboss' ),
		'default'     => 'yes',
		'options'     => array(
			array(
				'name'  => esc_html__( 'Yes, send email', 'buddyboss' ),
				'value' => 'yes',
			),
			array(
				'name'  => esc_html__( 'No, do not send email', 'buddyboss' ),
				'value' => 'no',
			),
		),
	);

	$array['activity'] = $activity_notification;

	return $array;
}
// add_filter( 'bb_register_notification_preferences', 'bb_activity_register_notifications', 10, 1 );


add_action(
	'bp_init',
	function () {
		new BP_Activity_Notification();
	}
);
