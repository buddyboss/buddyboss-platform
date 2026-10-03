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
 * Get (and lazily create) the cache-busting incrementor for a user's
 * notification counts.
 *
 * The value is embedded into every notification-count cache key built by
 * bp_notifications_get_count_cache_key(). Bumping it via
 * bp_notifications_bump_user_count_cache_incrementor() makes every
 * previously cached count for that user - no matter which component_name/
 * item_id/etc. combination produced it - unreachable in one operation,
 * without needing to enumerate or individually delete each variant's key.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id User ID.
 * @return string Incrementor value.
 */
function bp_notifications_get_user_count_cache_incrementor( $user_id ) {
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
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id User ID.
 */
function bp_notifications_bump_user_count_cache_incrementor( $user_id ) {
	wp_cache_delete( $user_id, 'bp_notifications_count_incrementor' );
}

/**
 * Build a cache key for a notification count query that reflects every
 * argument affecting the underlying SQL, not just the user ID.
 *
 * Used by BP_Notifications_Notification::get_total_count() so that two
 * queries for the same user with different filters (e.g. an unfiltered
 * count vs. a component_name-filtered one) never collide on the same
 * cache slot. See PROD-10344.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int   $user_id User ID the count belongs to.
 * @param array $args    The full parsed args used to build the count query
 *                       (see BP_Notifications_Notification::get_total_count()).
 * @return string Cache key.
 */
function bp_notifications_get_count_cache_key( $user_id, $args ) {
	// Only the args that actually shape the SQL WHERE clause should
	// participate in the key.
	$relevant = array_intersect_key(
		$args,
		array_flip(
			array(
				'id',
				'item_id',
				'secondary_item_id',
				'component_name',
				'component_action',
				'excluded_action',
				'search_terms',
				'date_query',
				'meta_query',
			)
		)
	);

	$incrementor = bp_notifications_get_user_count_cache_incrementor( $user_id );

	return $user_id . ':' . $incrementor . ':' . md5( maybe_serialize( $relevant ) );
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
	bp_notifications_bump_user_count_cache_incrementor( $user_id );
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
