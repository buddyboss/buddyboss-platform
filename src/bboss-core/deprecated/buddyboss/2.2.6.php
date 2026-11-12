<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.2.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
function bboss_pre_notify_reply_subscribers( $reply_id, $topic_id, $user_ids ) {
	_deprecated_function( __FUNCTION__, '2.2.6' );

	if ( bboss_enabled_legacy_email_preference() || ! bboss_is_active( 'notifications' ) || empty( $user_ids ) ) {
		return;
	}

	$user_ids        = wp_parse_id_list( $user_ids );
	$reply_author_id = bboss_bbp_get_reply_author_id( $reply_id );
	$reply_to_id     = bboss_bbp_get_reply_to( $reply_id );

	// Remove Topic author from the users.
	$unset_reply_key = array_search( $reply_author_id, $user_ids, true );
	if ( false !== $unset_reply_key ) {
		unset( $user_ids[ $unset_reply_key ] );
	}

	if ( ! empty( $reply_to_id ) ) {
		$reply_to_author_id = bboss_bbp_get_reply_author_id( $reply_to_id );

		$unset_reply_to_key = array_search( $reply_to_author_id, $user_ids, true );
		if ( false !== $unset_reply_to_key ) {
			unset( $user_ids[ $unset_reply_to_key ] );
		}
	}

	$action    = 'bb_forums_subscribed_reply';
	$min_count = (int) apply_filters( 'bb_forums_subscribed_reply_notifications_count', 20 );

	if (
		function_exists( 'bboss_notifications_background_enabled' ) &&
		true === bboss_notifications_background_enabled() &&
		count( $user_ids ) > $min_count
	) {
		global $bb_notifications_background_updater;
		$bb_notifications_background_updater->data(
			array(
				array(
					'callback' => 'bboss_add_background_notifications',
					'args'     => array(
						$user_ids,
						$reply_id,
						$reply_author_id,
						bboss_bbp_get_component_name(),
						$action,
						bboss_core_current_time(),
						true,
					),
				),
			)
		);
		$bb_notifications_background_updater->save()->dispatch();
	} else {
		foreach ( $user_ids as $user_id ) {
			bboss_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $reply_id,
					'secondary_item_id' => $reply_author_id,
					'component_name'    => bboss_bbp_get_component_name(),
					'component_action'  => $action,
					'date_notified'     => bboss_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
	}
}

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
function bboss_pre_notify_forum_subscribers( $topic_id, $forum_id, $user_ids ) {
	_deprecated_function( __FUNCTION__, '2.2.6' );

	if ( bboss_enabled_legacy_email_preference() || ! bboss_is_active( 'notifications' ) || empty( $user_ids ) ) {
		return;
	}

	$user_ids        = wp_parse_id_list( $user_ids );
	$topic_author_id = bboss_bbp_get_topic_author_id( $topic_id );

	// Remove Topic author from the users.
	$unset_topic_key = array_search( $topic_author_id, $user_ids, true );
	if ( false !== $unset_topic_key ) {
		unset( $user_ids[ $unset_topic_key ] );
	}

	$min_count = (int) apply_filters( 'bb_forums_subscribed_discussion_notifications_count', 20 );
	if (
		function_exists( 'bboss_notifications_background_enabled' ) &&
		true === bboss_notifications_background_enabled() &&
		count( $user_ids ) > $min_count
	) {
		global $bb_notifications_background_updater;
		$bb_notifications_background_updater->data(
			array(
				array(
					'callback' => 'bboss_add_background_notifications',
					'args'     => array(
						$user_ids,
						$topic_id,
						$topic_author_id,
						bboss_bbp_get_component_name(),
						'bb_forums_subscribed_discussion',
						bboss_core_current_time(),
						true,
					),
				),
			)
		);
		$bb_notifications_background_updater->save()->dispatch();
	} else {
		foreach ( $user_ids as $user_id ) {
			bboss_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $topic_id,
					'secondary_item_id' => $topic_author_id,
					'component_name'    => bboss_bbp_get_component_name(),
					'component_action'  => 'bb_forums_subscribed_discussion',
					'date_notified'     => bboss_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
	}
}
