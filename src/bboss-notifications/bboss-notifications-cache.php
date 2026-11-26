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
function bboss_notifications_update_meta_cache( $notification_ids = false ) {
	bboss_update_meta_cache(
		array(
			'object_ids'       => $notification_ids,
			'object_type'      => bboss()->notifications->id,
			'cache_group'      => 'notification_meta',
			'object_column'    => 'notification_id',
			'meta_table'       => bboss()->notifications->table_name_meta,
			'cache_key_prefix' => 'bp_notifications_meta',
		)
	);
}

/**
 * Get (and lazily create) the cache-busting incrementor for a user's
 * notification counts.
 *
 * The value is embedded into every notification-count cache key built by
 * bboss_notifications_get_count_cache_key(). Bumping it via
 * bboss_notifications_bump_user_count_cache_incrementor() makes every
 * previously cached count for that user - no matter which component_name/
 * item_id/etc. combination produced it - unreachable in one operation,
 * without needing to enumerate or individually delete each variant's key.
 *
 * @since BuddyBoss 3.8.1
 *
 * @param int $user_id User ID.
 * @return string Incrementor value.
 */
function bboss_notifications_get_user_count_cache_incrementor( $user_id ) {
	$incrementor = wp_cache_get( $user_id, 'bp_notifications_count_incrementor' );

	if ( false === $incrementor ) {
		$incrementor = microtime();
		wp_cache_set( $user_id, $incrementor, 'bp_notifications_count_incrementor' );
	}

	return $incrementor;
}

/**
 * Invalidate every cached notification count for a user, regardless of
 * which component_name/item_id/etc. arguments produced it.
 *
 * @since BuddyBoss 3.8.1
 *
 * @param int $user_id User ID.
 */
function bboss_notifications_bump_user_count_cache_incrementor( $user_id ) {
	wp_cache_delete( $user_id, 'bp_notifications_count_incrementor' );
}

/**
 * Build a cache key for a notification count query.
 *
 * The key is a hash of the final count SQL plus the user's incrementor, so it
 * reflects every input that shapes the query (including is_new, meta/date
 * queries and conditions added through `bb_notifications_get_where_conditions`)
 * and is always a plain 32-character hash, which is safe for Memcached.
 *
 * Used by BBoss_Notifications_Notification::get_total_count() so that two queries
 * for the same user with different filters never share a cache slot. See PROD-10344.
 *
 * @since BuddyBoss 3.8.1
 *
 * @param int    $user_id User ID the count belongs to.
 * @param string $sql     The final SQL of the count query.
 *
 * @return string Cache key.
 */
function bboss_notifications_get_count_cache_key( $user_id, $sql ) {
	global $wpdb;

	$incrementor = bboss_notifications_get_user_count_cache_incrementor( $user_id );

	// Remove the placeholder escape strings from the query.
	$sql = $wpdb->remove_placeholder_escape( $sql );

	return md5( $sql . $incrementor );
}

/**
 * Clear all notifications cache for a given user ID.
 *
 * @since BuddyPress 2.3.0
 * @since BuddyBoss 3.8.1 Invalidates counts by bumping the user's count incrementor instead of deleting $user_id keys.
 *
 * @param int $user_id The user ID's cache to clear.
 */
function bboss_notifications_clear_all_for_user_cache( $user_id = 0 ) {
	wp_cache_delete( 'all_for_user_' . $user_id, 'bp_notifications' );
	wp_cache_delete( $user_id, 'bp_notifications_grouped_notifications' );

	// Unread/read counts are cached under a hash of the query (see
	// bp_notifications_get_count_cache_key()), not under $user_id, so bumping
	// the incrementor - rather than a direct delete() - is what invalidates
	// them all at once. See PROD-10344.
	bboss_notifications_bump_user_count_cache_incrementor( $user_id );
}

/**
 * Invalidate 'all_for_user_' cache when saving.
 *
 * @since BuddyPress 2.0.0
 *
 * @param BBoss_Notifications_Notification $n Notification object.
 */
function bboss_notifications_clear_all_for_user_cache_after_save( BBoss_Notifications_Notification $n ) {
	bboss_notifications_clear_all_for_user_cache( $n->user_id );
	wp_cache_delete( $n->id, 'bp_notifications' );
	wp_cache_delete( 'bp_notifications_check_access_' . $n->user_id . '_' . $n->id, 'bp_notifications' );
}
add_action( 'bp_notification_after_save', 'bboss_notifications_clear_all_for_user_cache_after_save' );

/**
 * Invalidate the 'all_for_user_' cache when deleting.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $args Notification deletion arguments.
 */
function bboss_notifications_clear_all_for_user_cache_before_delete( $args ) {

	// Pull up a list of items matching the args (those about te be deleted).
	$notifications = BBoss_Notifications_Notification::get( $args );

	$user_ids = array();
	foreach ( $notifications as $notification ) {
		$user_ids[] = $notification->user_id;
	}

	$user_ids = array_unique( $user_ids );
	foreach ( $user_ids as $user_id ) {
		bboss_notifications_clear_all_for_user_cache( $user_id );
	}

	foreach ( $notifications as $notification ) {
		wp_cache_delete( $notification->id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $notification->user_id . '_' . $notification->id, 'bp_notifications' );
	}
}
add_action( 'bp_notification_before_delete', 'bboss_notifications_clear_all_for_user_cache_before_delete' );

/**
 * Invalidate the caches of the users whose notifications were deleted.
 *
 * The before_delete handler looks rows up through BBoss_Notifications_Notification::get(),
 * which defaults to unread rows of registered components only, so deleting a read
 * notification did not clear its user's counts. delete() passes the exact rows it
 * removed to this action, so clearing from them covers every deleted row.
 *
 * @since BuddyBoss 3.8.1
 *
 * @param int|false $retval        Number of rows deleted on success, false on failure.
 * @param array     $notifications Deleted notification rows.
 */
function bboss_notifications_clear_all_for_user_cache_after_delete( $retval, $notifications ) {
	if ( empty( $retval ) || empty( $notifications ) ) {
		return;
	}

	$user_ids = array_unique( wp_list_pluck( $notifications, 'user_id' ) );
	foreach ( $user_ids as $user_id ) {
		bboss_notifications_clear_all_for_user_cache( $user_id );
	}

	foreach ( $notifications as $notification ) {
		wp_cache_delete( $notification->id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $notification->user_id . '_' . $notification->id, 'bp_notifications' );
	}
}
add_action( 'bp_notification_after_delete', 'bboss_notifications_clear_all_for_user_cache_after_delete', 10, 2 );

/**
 * Invalidates 'all_for_user_' cache when updating.
 *
 * @since BuddyPress 2.3.0
 * @since BuddyBoss 3.8.1 Clears every affected user when the WHERE has no user_id or id.
 *
 * @param array $update_args See BBoss_Notifications_Notification::update() for description.
 * @param array $where_args  See BBoss_Notifications_Notification::update() for description.
 */
function bboss_notifications_clear_all_for_user_cache_before_update( $update_args, $where_args ) {

	// User ID is passed in where arugments.
	if ( ! empty( $where_args['user_id'] ) ) {
		bboss_notifications_clear_all_for_user_cache( $where_args['user_id'] );
		if ( ! empty( $where_args['id'] ) ) {
			wp_cache_delete( $where_args['id'], 'bp_notifications' );
			wp_cache_delete( 'bp_notifications_check_access_' . $where_args['user_id'] . '_' . $where_args['id'], 'bp_notifications' );
		}

		// Get user ID from Notification ID.
	} elseif ( ! empty( $where_args['id'] ) ) {
		$n = bboss_notifications_get_notification( $where_args['id'] );
		bboss_notifications_clear_all_for_user_cache( $n->user_id );
		wp_cache_delete( $n->id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $n->user_id . '_' . $n->id, 'bp_notifications' );

		// No user or notification ID (mark_all_by_type(), mark_all_from_user(), mark by item for all users):
		// clear every user whose rows match the update's WHERE.
	} else {
		bboss_notifications_clear_cache_for_update_where( $where_args );
	}
}
add_action( 'bp_notification_before_update', 'bboss_notifications_clear_all_for_user_cache_before_update', 10, 2 );

/**
 * Clear the caches of every user whose notifications match an update's WHERE arguments.
 *
 * Used when BBoss_Notifications_Notification::update() is called without a user_id or id,
 * e.g. by mark_all_by_type(), mark_all_from_user() or bboss_notifications_mark_notifications_by_item_id()
 * with no user. It reads the matching rows with the same column = value conditions that
 * update() applies, so no default component, is_new or moderation filter can skip a row.
 *
 * @since BuddyBoss 3.8.1
 *
 * @param array $where_args See BBoss_Notifications_Notification::update() for description.
 */
function bboss_notifications_clear_cache_for_update_where( $where_args ) {
	global $wpdb;

	// Same columns and formats as BP_Notifications_Notification::get_query_clauses().
	$conditions = array();
	foreach ( array( 'item_id', 'secondary_item_id' ) as $column ) {
		if ( ! empty( $where_args[ $column ] ) ) {
			$conditions[] = $wpdb->prepare( "{$column} = %d", absint( $where_args[ $column ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
	foreach ( array( 'component_name', 'component_action' ) as $column ) {
		if ( ! empty( $where_args[ $column ] ) ) {
			$conditions[] = $wpdb->prepare( "{$column} = %s", $where_args[ $column ] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
	if ( isset( $where_args['is_new'] ) ) {
		$conditions[] = $wpdb->prepare( 'is_new = %d', ! empty( $where_args['is_new'] ) ? 1 : 0 );
	}

	// update() with no WHERE matches nothing, so there is nothing to clear.
	if ( empty( $conditions ) ) {
		return;
	}

	$table         = bboss()->notifications->table_name;
	$notifications = $wpdb->get_results( "SELECT id, user_id FROM {$table} WHERE " . implode( ' AND ', $conditions ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( empty( $notifications ) ) {
		return;
	}

	$user_ids = array_unique( wp_list_pluck( $notifications, 'user_id' ) );
	foreach ( $user_ids as $user_id ) {
		bboss_notifications_clear_all_for_user_cache( $user_id );
	}

	foreach ( $notifications as $notification ) {
		wp_cache_delete( $notification->id, 'bp_notifications' );
		wp_cache_delete( 'bp_notifications_check_access_' . $notification->user_id . '_' . $notification->id, 'bp_notifications' );
	}
}
