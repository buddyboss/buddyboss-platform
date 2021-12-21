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
 * @return array|string
 */
function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'friendship_accepted':
			$link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends' );

			// $action and $amount are used to generate dynamic filter names.
			$action = 'accepted';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( '%d members accepted your connection requests', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text   = sprintf( __( '%s accepted your request to connect', 'buddyboss' ), bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;

		case 'friendship_request':
			$link = bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/?new';

			$action = 'request';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( 'You have %d pending requests to connect', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text   = sprintf( __( '%s sent you an invitation to connect', 'buddyboss' ), bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format.
	if ( 'string' == $format ) {

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
		 *
		 * @param string|array $value       Depending on format, an HTML link to new requests profile
		 *                                  tab or array with link and text.
		 * @param int          $total_items The total number of messaging-related notifications
		 *                                  waiting for the user.
		 * @param int          $item_id     The primary item ID.
		 */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id );
	} else {
		/** This filter is documented in bp-friends/bp-friends-notifications.php */
		$return = apply_filters(
			'bp_friends_' . $amount . '_friendship_' . $action . '_notification',
			array(
				'link' => $link,
				'text' => $text,
			),
			(int) $total_items,
			$item_id
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
function friends_clear_friend_notifications() {
	if ( isset( $_GET['new'] ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
	}
}
add_action( 'bp_activity_screen_my_activity', 'friends_clear_friend_notifications' );

/**
 * Delete any friendship request notifications for the logged in user.
 *
 * @since BuddyPress 1.9.0
 */
function bp_friends_mark_friendship_request_notifications_by_type() {
	if ( isset( $_GET['new'] ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_screen_requests', 'bp_friends_mark_friendship_request_notifications_by_type' );

/**
 * Delete any friendship acceptance notifications for the logged in user.
 *
 * @since BuddyPress 1.9.0
 */
function bp_friends_mark_friendship_accepted_notifications_by_type() {
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
}
add_action( 'friends_screen_my_friends', 'bp_friends_mark_friendship_accepted_notifications_by_type' );

/**
 * Notify one use that another user has requested their virtual friendship.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bp_friends_friendship_requested_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {
	bp_notifications_add_notification(
		array(
			'user_id'           => $friend_user_id,
			'item_id'           => $initiator_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => buddypress()->friends->id,
			'component_action'  => 'friendship_request',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
}
add_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification', 10, 3 );

/**
 * Remove friend request notice when a member rejects another members
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship object.
 */
function bp_friends_mark_friendship_rejected_notifications_by_item_id( $friendship_id, $friendship ) {
	bp_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_friendship_rejected', 'bp_friends_mark_friendship_rejected_notifications_by_item_id', 10, 2 );

/**
 * Notify a member when another member accepts their virtual friendship request.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bp_friends_add_friendship_accepted_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {
	// Remove the friend request notice.
	bp_notifications_mark_notifications_by_item_id( $friend_user_id, $initiator_user_id, buddypress()->friends->id, 'friendship_request' );

	// Add a friend accepted notice for the initiating user.
	bp_notifications_add_notification(
		array(
			'user_id'           => $initiator_user_id,
			'item_id'           => $friend_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => buddypress()->friends->id,
			'component_action'  => 'friendship_accepted',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
}
add_action( 'friends_friendship_accepted', 'bp_friends_add_friendship_accepted_notification', 10, 3 );

/**
 * Remove friend request notice when a member withdraws their friend request.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship Object.
 */
function bp_friends_mark_friendship_withdrawn_notifications_by_item_id( $friendship_id, $friendship ) {
	bp_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_mark_friendship_withdrawn_notifications_by_item_id', 10, 2 );

/**
 * Remove connection requests FROM user, used primarily when a user is deleted.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id ID of the user whose notifications are removed.
 */
function bp_friends_remove_notifications_data( $user_id = 0 ) {
	bp_notifications_delete_notifications_from_user( $user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_remove_data', 'bp_friends_remove_notifications_data', 10, 1 );

/**
 * Add Connections-related settings to the Settings > Notifications page.
 *
 * @since BuddyPress 1.0.0
 */
function friends_screen_notification_settings() {

	$options                  = bb_register_notification_preferences( buddypress()->friends->id );
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

				$options = bb_notification_preferences_types( $field, bp_loggedin_user_id() );
				?>
                <tr>
                    <td><?php echo( isset( $field['label'] ) ? esc_html( $field['label'] ) : '' ); ?></td>

					<?php
					foreach ( $options as $key => $v ) {
						$is_disabled = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_enabled', false );
						$is_render   = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $field['key'], $key );
						$name        = ( 'email' === $key ) ? 'notifications[' . $field['key'] . ']' : 'notifications[' . $field['key'] . '_' . $key . ']';
						if ( $is_render ) {
							?>
                            <td class="<?php echo esc_attr( $key ); ?>">
                                <input type="checkbox" id="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>" name="<?php echo esc_attr( $name ); ?>" class="bs-styled-checkbox" value="yes" <?php checked( $v['is_checked'], 'yes' ); ?> />
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

add_action( 'bp_notification_settings', 'friends_screen_notification_settings', 15 );

/**
 * Add Notifications for the friends.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $array Array of notifications.
 *
 * @return mixed
 */
function bb_friends_register_notifications( $array ) {
	$friends_notification = array(
		'label'  => esc_html__( 'Connections', 'buddyboss' ),
		'fields' => array(
			array(
				'key'         => 'notification_friends_friendship_request',
				'admin_label' => esc_html__( 'A member invites you to connect', 'buddyboss' ),
				'label'       => esc_html__( 'A member invites you to connect', 'buddyboss' ),
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
			),
			array(
				'key'         => 'notification_friends_friendship_accepted',
				'admin_label' => esc_html__( 'A member accepts your connection request', 'buddyboss' ),
				'label'       => esc_html__( 'A member accepts your connection request', 'buddyboss' ),
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
			),
		),
	);

	$array['friends'] = $friends_notification;

	return $array;
}

//add_filter( 'bb_register_notification_preferences', 'bb_friends_register_notifications', 14, 1 );
