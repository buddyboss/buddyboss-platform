<?php
/**
 * Functions related to notifications caching.
 *
 * @package BuddyBoss\Notifications\Cache
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up metadata for a set of notifications.
 *
 * It grabs all notification meta associated with all of the notifications
 * passed in $notification_ids and adds it to WP cache. This improves efficiency
 * when using notification meta within a loop context.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int|string|array|bool $notification_ids Accepts a single notification_id, or a
 *                                                comma-separated list or array of
 *                                                notification ids.
 */
function bp_notifications_update_meta_cache( $notification_ids = false ) {
	bp_update_meta_cache(
		array(
			'object_ids'       => $notification_ids,
			'object_type'      => buddypress()->notifications->id,
			'cache_group'      => 'notification_meta',
			'object_column'    => 'notification_id',
			'meta_table'       => buddypress()->notifications->table_name_meta,
			'cache_key_prefix' => 'bp_notifications_meta',
		)
	);
}

/**
 * Clear all notifications cache for a given user ID.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int $user_id The user ID's cache to clear.
 */
function bp_notifications_clear_all_for_user_cache( $user_id = 0 ) {
	wp_cache_delete( 'all_for_user_' . $user_id, 'bp_notifications' );
	wp_cache_delete( $user_id, 'bp_notifications_unread_count' );
	wp_cache_delete( $user_id, 'bp_notifications_read_count' );
	wp_cache_delete( $user_id, 'bp_notifications_grouped_notifications' );
}

/**
 * Invalidate 'all_for_user_' cache when saving.
 *
 * @since BuddyPress 2.0.0
 *
 * @param BP_Notifications_Notification $n Notification object.
 */
function bp_notifications_clear_all_for_user_cache_after_save( BP_Notifications_Notification $n ) {
	bp_notifications_clear_all_for_user_cache( $n->user_id );
	wp_cache_delete( $n->id, 'bp_notifications' );
	wp_cache_delete( 'bp_notifications_check_access_' . $n->user_id . '_' . $n->id, 'bp_notifications' );
}
add_action( 'bp_notification_after_save', 'bp_notifications_clear_all_for_user_cache_after_save' );

/**
 * Invalidate the 'all_for_user_' cache when deleting.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $args Notification deletion arguments.
 */
function bp_notifications_clear_all_for_user_cache_before_delete( $args ) {

	// Pull up a list of items matching the args (those about te be deleted).
	$notifications = BP_Notifications_Notification::get( $args );

	$user_ids = array();
	foreach ( $notifications as $notification ) {
		$user_ids[] = $notification->user_id;
	}

	$user_ids = array_unique( $user_ids );
	foreach ( $user_ids as $user_id ) {
		bp_notifications_clear_all_for_user_cache( $user_id );
	}

	foreach ( $notifications as $notification ) {
		wp_cache_delete( $notification->id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $notification->user_id . '_' . $notification->id, 'bp_notifications' );
	}
}
add_action( 'bp_notification_before_delete', 'bp_notifications_clear_all_for_user_cache_before_delete' );

/**
 * Invalidates 'all_for_user_' cache when updating.
 *
 * @since BuddyPress 2.3.0
 *
 * @param array $update_args See BP_Notifications_Notification::update() for description.
 * @param array $where_args  See BP_Notifications_Notification::update() for description.
 */
function bp_notifications_clear_all_for_user_cache_before_update( $update_args, $where_args ) {

	// User ID is passed in where arugments.
	if ( ! empty( $where_args['user_id'] ) ) {
		bp_notifications_clear_all_for_user_cache( $where_args['user_id'] );
		if ( ! empty( $where_args['id'] ) ) {
			wp_cache_delete( $where_args['id'], 'bp_notifications' );
			wp_cache_delete( 'bp_notifications_check_access_' . $where_args['user_id'] . '_' . $where_args['id'], 'bp_notifications' );
		} else {
			bb_notifications_clear_item_cache_before_update( $update_args, $where_args );
		}

		// Get user ID from Notification ID.
	} elseif ( ! empty( $where_args['id'] ) ) {
		$n = bp_notifications_get_notification( $where_args['id'] );
		bp_notifications_clear_all_for_user_cache( $n->user_id );
		wp_cache_delete( $n->id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $n->user_id . '_' . $n->id, 'bp_notifications' );
	}
}
add_action( 'bp_notification_before_update', 'bp_notifications_clear_all_for_user_cache_before_update', 10, 2 );

/**
 * Invalidates the per-notification caches of a member's rows that an update without an ID changes.
 *
 * Updates such as bp_notifications_mark_notifications_by_item_id() match rows by user, item
 * and action instead of ID. Without this, a cached copy keeps the old 'is_new' value after the
 * row is marked read. Only the IDs of rows whose status changes are selected, and only for
 * updates scoped to one member.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $update_args See BP_Notifications_Notification::update() for description.
 * @param array $where_args  See BP_Notifications_Notification::update() for description.
 */
function bb_notifications_clear_item_cache_before_update( $update_args, $where_args ) {
	global $wpdb;

	if ( empty( $where_args['user_id'] ) || ! empty( $where_args['id'] ) ) {
		return;
	}

	// Same columns and rules as BP_Notifications_Notification::get_query_clauses().
	$columns = array(
		'user_id'           => '%d',
		'item_id'           => '%d',
		'secondary_item_id' => '%d',
		'component_name'    => '%s',
		'component_action'  => '%s',
	);

	$where  = array();
	$values = array();
	foreach ( $columns as $column => $format ) {
		if ( ! empty( $where_args[ $column ] ) ) {
			$where[]  = "{$column} = {$format}";
			$values[] = '%d' === $format ? absint( $where_args[ $column ] ) : $where_args[ $column ];
		}
	}

	if ( isset( $where_args['is_new'] ) ) {
		$where[]  = 'is_new = %d';
		$values[] = ! empty( $where_args['is_new'] ) ? 1 : 0;
	}

	// Rows that already have the new status keep a valid cache.
	if ( isset( $update_args['is_new'] ) ) {
		$where[]  = 'is_new != %d';
		$values[] = ! empty( $update_args['is_new'] ) ? 1 : 0;
	}

	$table = buddypress()->notifications->table_name;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders -- Columns and formats come from the fixed list above.
	$notification_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE " . implode( ' AND ', $where ), $values ) );

	$user_id = absint( $where_args['user_id'] );
	foreach ( $notification_ids as $notification_id ) {
		wp_cache_delete( (int) $notification_id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $user_id . '_' . $notification_id, 'bp_notifications' );
	}
}
