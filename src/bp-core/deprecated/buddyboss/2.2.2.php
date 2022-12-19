<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss [BBVERSION]
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
function bb_pre_notify_reply_subscribers( $reply_id, $topic_id, $user_ids ) {
	_deprecated_function( __FUNCTION__, '[BBVERSION]' );
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
function bb_pre_notify_forum_subscribers( $topic_id, $forum_id, $user_ids ) {
	_deprecated_function( __FUNCTION__, '[BBVERSION]' );
}
