<?php
/**
 * BuddyBoss Messages Caching.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyBoss\Messages\Cache
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up metadata for a set of messages.
 *
 * It grabs all message meta associated with all of the messages passed in
 * $message_ids and adds it to WP cache. This improves efficiency when using
 * message meta within a loop context.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int|string|array|bool $message_ids Accepts a single message_id, or a
 *                                           comma-separated list or array of message ids.
 */
function bp_messages_update_meta_cache( $message_ids = false ) {
	bp_update_meta_cache(
		array(
			'object_ids'       => $message_ids,
			'object_type'      => buddypress()->messages->id,
			'cache_group'      => 'message_meta',
			'object_column'    => 'message_id',
			'meta_table'       => buddypress()->messages->table_name_meta,
			'cache_key_prefix' => 'bp_messages_meta',
		)
	);
}

// List actions to clear super cached pages on, if super cache is installed.
add_action( 'messages_delete_thread', 'bp_core_clear_cache' );
add_action( 'messages_send_notice', 'bp_core_clear_cache' );
add_action( 'messages_message_sent', 'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic.
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox', 'bp_core_clear_cache' );

/**
 * Clear message cache after a message is saved.
 *
 * @since BuddyPress 2.0.0
 *
 * @param BP_Messages_Message $message Message being saved.
 */
function bp_messages_clear_cache_on_message_save( BP_Messages_Message $message ) {
	// Delete thread cache.
	// wp_cache_delete( $message->thread_id, 'bp_messages_threads' );
	bp_messages_delete_thread_paginated_messages_cache( $message->thread_id );

	// Delete unread count for each recipient.
	foreach ( (array) $message->recipients as $recipient ) {
		wp_cache_delete( $recipient->user_id, 'bp_messages_unread_count' );
		wp_cache_delete( "bb_thread_message_unread_count_{$recipient->user_id}_{$message->thread_id}", 'bp_messages_unread_count' );
	}

	// Delete thread recipient cache.
	wp_cache_delete( 'thread_recipients_' . $message->thread_id, 'bp_messages' );
}
add_action( 'messages_message_after_save', 'bp_messages_clear_cache_on_message_save' );
add_action( 'messages_message_sent', 'bp_messages_clear_cache_on_message_save', 1, 2 );

/**
 * Clear message cache after a message thread is deleted.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int|array $thread_ids If single thread, the thread ID.
 *                              Otherwise, an array of thread IDs.
 * @param int       $user_id    ID of the user that the threads were deleted for.
 */
function bp_messages_clear_cache_on_message_delete( $thread_ids, $user_id ) {
	// Delete thread and thread recipient cache.
	foreach ( (array) $thread_ids as $thread_id ) {
		// wp_cache_delete( $thread_id, 'bp_messages_threads' );
		bp_messages_delete_thread_paginated_messages_cache( $thread_id );
		wp_cache_delete( "thread_recipients_{$thread_id}", 'bp_messages' );
		wp_cache_delete( "bb_thread_message_unread_count_{$user_id}_{$thread_id}", 'bp_messages_unread_count' );
	}

	// Delete unread count for logged-in user.
	wp_cache_delete( $user_id, 'bp_messages_unread_count' );
}
add_action( 'messages_delete_thread', 'bp_messages_clear_cache_on_message_delete', 10, 2 );

/**
 * Invalidate cache for notices.
 *
 * Currently, invalidates active notice cache.
 *
 * @since BuddyPress 2.0.0
 *
 * @param BP_Messages_Notice $notice Notice that was saved.
 */
function bp_notices_clear_cache( $notice ) {
	wp_cache_delete( 'active_notice', 'bp_messages' );
}
add_action( 'messages_notice_after_save', 'bp_notices_clear_cache' );
add_action( 'messages_notice_before_delete', 'bp_notices_clear_cache' );

/**
 * Invalidate cache for thread pagination messages.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_messages_delete_thread_paginated_messages_cache( $thread_id ) {
	BP_Messages_Thread::$noCache = true;
	$thread_id                   = $thread_id;
	$before                      = null;
	$perpage                     = apply_filters( 'bp_messages_default_per_page', 10 );

	while ( wp_cache_get( "{$thread_id}{$before}{$perpage}", 'bp_messages_threads' ) ) {
		wp_cache_delete( "{$thread_id}{$before}{$perpage}", 'bp_messages_threads' );
		$messages = BP_Messages_Thread::get_messages( $thread_id, $before, $perpage );

		if ( end( $messages ) ) {
			$before = end( $messages )->date_sent;
		}
	}

	BP_Messages_Thread::$noCache = false;
}

/**
 * Delete the messages cache on different actions.
 *
 * @since BuddyBoss 1.9.0
 */
function bb_core_clear_message_cache() {
	bp_core_reset_incrementor( 'bp_messages' );
}

add_action( 'messages_message_after_save', 'bb_core_clear_message_cache' );
add_action( 'messages_delete_thread', 'bb_core_clear_message_cache' );
add_action( 'messages_send_notice', 'bb_core_clear_message_cache' );
add_action( 'messages_message_sent', 'bb_core_clear_message_cache' );
add_action( 'messages_thread_mark_as_read', 'bb_core_clear_message_cache' );
add_action( 'messages_thread_mark_as_unread', 'bb_core_clear_message_cache' );

/**
 * Clear cache when group messages has been disabled by admin.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $old_value Old values of array.
 * @param array $value     New values of the array.
 *
 * @return void
 */
function bb_clear_cache_while_group_messsage_settings_updated( $old_value, $value ) {

	if ( $old_value !== $value ) {
		global $wp_object_cache;
		if ( isset( $wp_object_cache->cache['bp_messages_unread_count'] ) ) {
			unset( $wp_object_cache->cache['bp_messages_unread_count'] );
		}

		bp_core_reset_incrementor( 'bp_messages' );
	}
}

add_action( 'update_option_bp-disable-group-messages', 'bb_clear_cache_while_group_messsage_settings_updated', 10, 2 );

/**
 * Clear unread message count cache after archive/un-archive thread.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id Thread ID.
 * @param int $user_id   User ID.
 *
 * @return void
 */
function bp_messages_clear_message_unread_cache_on_thread_archived( $thread_id, $user_id ) {
	wp_cache_delete( $user_id, 'bp_messages_unread_count' );
	wp_cache_delete( "bb_thread_message_unread_count_{$user_id}_{$thread_id}", 'bp_messages_unread_count' );
}
add_action( 'bb_messages_thread_archived', 'bp_messages_clear_message_unread_cache_on_thread_archived', 1, 2 );
add_action( 'bb_messages_thread_unarchived', 'bp_messages_clear_message_unread_cache_on_thread_archived', 1, 2 );
