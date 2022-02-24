<?php
/**
 * Functions related to the BuddyBoss Activity component and the WP Cache.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up activitymeta for a specified set of activity items.
 *
 * It grabs all activitymeta associated with all of the activity items passed
 * in $activity_ids and adds it to the WP cache. This improves efficiency when
 * using querying activitymeta inline.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int|string|array|bool $activity_ids Accepts a single activity ID, or a comma-
 *                                            separated list or array of activity ids.
 */
function bp_activity_update_meta_cache( $activity_ids = false ) {
	$bp = buddypress();

	$cache_args = array(
		'object_ids'       => $activity_ids,
		'object_type'      => $bp->activity->id,
		'object_column'    => 'activity_id',
		'cache_group'      => 'activity_meta',
		'meta_table'       => $bp->activity->table_name_meta,
		'cache_key_prefix' => 'bp_activity_meta',
	);

	bp_update_meta_cache( $cache_args );
}

/**
 * Clear a cached activity item when that item is updated.
 *
 * @since BuddyPress 2.0.0
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_activity_clear_cache_for_activity( $activity ) {
	wp_cache_delete( $activity->id, 'bp_activity' );
	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );
	wp_cache_delete( 'bb_activity_hierarchy_' . $activity->id, 'bp_activity' );     // Used in bb_get_activity_hierarchy().
	wp_cache_delete( 'bp_media_activity_id_' . $activity->id, 'bp_media' );         // Used in get_activity_media_id().
	wp_cache_delete( 'bp_media_attachment_id_' . $activity->id, 'bp_media' );       // Used in get_activity_attachment_id().
	wp_cache_delete( 'bp_document_activity_id_' . $activity->id, 'bp_document' );   // Used in get_activity_document_id().
	wp_cache_delete( 'bp_document_attachment_id_' . $activity->id, 'bp_document' ); // Used in get_activity_attachment_id().
	wp_cache_delete( 'bp_video_activity_id_' . $activity->id, 'bp_video' );         // Used in get_activity_video_id().
	wp_cache_delete( 'bp_video_attachment_id_' . $activity->id, 'bp_video' );       // Used in get_activity_attachment_id().

	if ( ! empty( $activity->secondary_item_id ) ) {
		wp_cache_delete( 'bp_get_child_comments_' . $activity->secondary_item_id, 'bp_activity_comments' ); // Used in BP_Activity_Activity::get_child_comments().
	}
}
add_action( 'bp_activity_after_save', 'bp_activity_clear_cache_for_activity' );

/**
 * Clear cached data for deleted activity items.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $deleted_ids IDs of deleted activity items.
 */
function bp_activity_clear_cache_for_deleted_activity( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_activity' );
		wp_cache_delete( 'bb_activity_hierarchy_' . $deleted_id, 'bp_activity' );     // Used in bb_get_activity_hierarchy().
		wp_cache_delete( 'bp_media_activity_id_' . $deleted_id, 'bp_media' );         // Used in get_activity_media_id().
		wp_cache_delete( 'bp_media_attachment_id_' . $deleted_id, 'bp_media' );       // Used in get_activity_attachment_id().
		wp_cache_delete( 'bp_document_activity_id_' . $deleted_id, 'bp_document' );   // Used in get_activity_document_id().
		wp_cache_delete( 'bp_document_attachment_id_' . $deleted_id, 'bp_document' ); // Used in get_activity_attachment_id().
		wp_cache_delete( 'bp_video_activity_id_' . $deleted_id, 'bp_video' );         // Used in get_activity_video_id().
		wp_cache_delete( 'bp_video_attachment_id_' . $deleted_id, 'bp_video' );       // Used in get_activity_attachment_id().
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_activity_clear_cache_for_deleted_activity' );

/**
 * Clear cached data for deleted activity items.
 *
 * @since BuddyBoss 1.9.0
 *
 * @param array $activities Array of activities.
 */
function bb_activity_clear_cache_after_deleted_activity( $activities ) {
	if ( ! empty( $activities ) ) {
		foreach ( (array) $activities as $activity ) {
			wp_cache_delete( $activity->id, 'bp_activity' );
			wp_cache_delete( 'bb_activity_hierarchy_' . $activity->id, 'bp_activity' );     // Used in bb_get_activity_hierarchy().
			wp_cache_delete( 'bp_media_activity_id_' . $activity->id, 'bp_media' );         // Used in get_activity_media_id().
			wp_cache_delete( 'bp_media_attachment_id_' . $activity->id, 'bp_media' );       // Used in get_activity_attachment_id().
			wp_cache_delete( 'bp_document_activity_id_' . $activity->id, 'bp_document' );   // Used in get_activity_document_id().
			wp_cache_delete( 'bp_document_attachment_id_' . $activity->id, 'bp_document' ); // Used in get_activity_attachment_id().
			wp_cache_delete( 'bp_video_activity_id_' . $activity->id, 'bp_video' );         // Used in get_activity_video_id().
			wp_cache_delete( 'bp_video_attachment_id_' . $activity->id, 'bp_video' );       // Used in get_activity_attachment_id().
			if ( ! empty( $activity->secondary_item_id ) ) {
				wp_cache_delete( 'bp_get_child_comments_' . $activity->secondary_item_id, 'bp_activity_comments' ); // Used in BP_Activity_Activity::get_child_comments().
			}
		}
	}
}
add_action( 'bp_activity_after_delete', 'bb_activity_clear_cache_after_deleted_activity', 10, 1 );

/**
 * Reset cache incrementor for the Activity component.
 *
 * Called whenever an activity item is created, updated, or deleted, this
 * function effectively invalidates all cached results of activity queries.
 *
 * @since BuddyPress 2.7.0
 *
 * @return bool True on success, false on failure.
 */
function bp_activity_reset_cache_incrementor() {
	$without_last_activity = bp_core_reset_incrementor( 'bp_activity' );
	$with_last_activity    = bp_core_reset_incrementor( 'bp_activity_with_last_activity' );
	return $without_last_activity && $with_last_activity;
}
add_action( 'bp_activity_delete', 'bp_activity_reset_cache_incrementor' );
add_action( 'bp_activity_add', 'bp_activity_reset_cache_incrementor' );
add_action( 'added_activity_meta', 'bp_activity_reset_cache_incrementor' );
add_action( 'updated_activity_meta', 'bp_activity_reset_cache_incrementor' );
add_action( 'deleted_activity_meta', 'bp_activity_reset_cache_incrementor' );

/**
 * Clear cached data for deleted users.
 *
 * @since BuddyBoss 1.1.7
 *
 * @param int $user_id ID of the user.
 */
function bp_activity_follow_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_total_follower_for_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_total_following_for_user_' . $user_id, 'bp' );
}
add_action( 'bp_remove_follow_data', 'bp_activity_follow_clear_user_object_cache' );

/**
 * Clear cached data for deleted users and reset incrementor.
 *
 * @since BuddyBoss 1.1.7
 *
 * @param object $follow Follow object.
 */
function bp_activity_follow_reset_cache_incrementor( $follow ) {

	if ( ! empty( $follow->leader_id ) ) {
		wp_cache_delete( 'bp_total_follower_for_user_' . $follow->leader_id, 'bp' );
		wp_cache_delete( 'bp_total_following_for_user_' . $follow->leader_id, 'bp' );
	}

	if ( ! empty( $follow->follower_id ) ) {
		wp_cache_delete( 'bp_total_follower_for_user_' . $follow->follower_id, 'bp' );
		wp_cache_delete( 'bp_total_following_for_user_' . $follow->follower_id, 'bp' );
	}

	return bp_core_reset_incrementor( 'bp_activity_follow' );
}
add_action( 'bp_start_following', 'bp_activity_follow_reset_cache_incrementor' );
add_action( 'bp_stop_following', 'bp_activity_follow_reset_cache_incrementor' );

/**
 * Clear cached data for follow object.
 *
 * @since BuddyBoss 1.1.7
 *
 * @param object $follow Follow object.
 */
function bp_activity_follow_delete_object_cache( $follow ) {
	if ( ! empty( $follow->id ) ) {
		wp_cache_delete( $follow->id, 'bp_activity_follow' );
		bp_core_delete_incremented_cache( $follow->leader_id . '_' . $follow->follower_id, 'bp_activity_follow' );
	}
}
add_action( 'bp_stop_following', 'bp_activity_follow_delete_object_cache' );

/**
 * Clear cached data for follow object when user is deleted.
 *
 * @since BuddyBoss 1.1.7
 *
 * @param int        $user_id ID of user.
 * @param array|bool $ids array of follow ids or false.
 */
function bp_activity_follow_delete_follow_ids_object_cache( $user_id, $ids ) {
	if ( ! empty( $ids ) ) {
		foreach ( $ids as $id ) {
			if ( is_object( $id ) && isset( $id->id ) ) {
				wp_cache_delete( $id->id, 'bp_activity_follow' );
			} else {
				wp_cache_delete( $id, 'bp_activity_follow' );
			}
		}
	}
}
add_action( 'bp_remove_follow_data', 'bp_activity_follow_delete_follow_ids_object_cache', 10, 2 );
