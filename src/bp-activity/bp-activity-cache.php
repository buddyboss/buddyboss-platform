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
	wp_cache_delete( $activity->id, 'activity_edit_data' );
	if ( 'activity_comment' === $activity->type ) {
		wp_cache_delete( 'comment_' . $activity->id, 'activity_edit_data' );
		// Clear comment chain cache. Used in bb_get_activity_comment_chain_info().
		wp_cache_delete( 'bb_comment_chain_' . $activity->id . '_' . $activity->item_id, 'bp_activity_comments' );
	}
	wp_cache_delete( 'bb_get_activity_status_' . $activity->id, 'bp_activity' );

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
		wp_cache_delete( $deleted_id, 'activity_edit_data' );
		wp_cache_delete( 'comment_' . $deleted_id, 'activity_edit_data' );
		wp_cache_delete( 'bb_get_activity_status_' . $deleted_id, 'bp_activity' );
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
			wp_cache_delete( $activity->id, 'activity_edit_data' );
			if ( 'activity_comment' === $activity->type ) {
				wp_cache_delete( 'comment_' . $activity->id, 'activity_edit_data' );
				// Clear comment chain cache. Used in bb_get_activity_comment_chain_info().
				wp_cache_delete( 'bb_comment_chain_' . $activity->id . '_' . $activity->item_id, 'bp_activity_comments' );
			}
			wp_cache_delete( 'bb_get_activity_status_' . $activity->id, 'bp_activity' );
			if ( ! empty( $activity->secondary_item_id ) ) {
				wp_cache_delete( 'bp_get_child_comments_' . $activity->secondary_item_id, 'bp_activity_comments' ); // Used in BP_Activity_Activity::get_child_comments().
				wp_cache_delete( $activity->secondary_item_id, 'bp_activity' );
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
add_action( 'bp_activity_mark_as_spam', 'bp_activity_reset_cache_incrementor' );
add_action( 'bp_activity_mark_as_ham', 'bp_activity_reset_cache_incrementor' );

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
 * @param array|bool $ids     array of follow ids or false.
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

/**
 * Store, or pull, the members on the other side of a user's follow relationships.
 *
 * `BP_Activity_Follow::delete_all_for_user()` deletes the rows with a direct query,
 * so the other members must be captured before the rows are gone and their
 * follower/following caches cleared afterwards.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int        $user_id  ID of the user whose follow data is being removed.
 * @param array|null $user_ids Optional. Member IDs to store. Omit to pull (return
 *                             and forget) the stored IDs.
 *
 * @return int[] IDs of the members who followed, or were followed by, the user.
 */
function bb_activity_follow_remove_data_related_users( $user_id, $user_ids = null ) {
	static $store = array();

	$user_id = (int) $user_id;

	if ( null !== $user_ids ) {
		$store[ $user_id ] = wp_parse_id_list( $user_ids );

		return $store[ $user_id ];
	}

	$user_ids = isset( $store[ $user_id ] ) ? $store[ $user_id ] : array();
	unset( $store[ $user_id ] );

	return $user_ids;
}

/**
 * Capture the members on the other side of a user's follow relationships
 * before the user's follow data is removed.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id ID of the user whose follow data is being removed.
 */
function bb_activity_follow_capture_related_users_before_remove_data( $user_id ) {
	global $wpdb;

	$user_id = (int) $user_id;
	if ( empty( $user_id ) ) {
		return;
	}

	$bp = buddypress();

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the rows about to be deleted so their caches can be cleared.
	$leader_ids = $wpdb->get_col( $wpdb->prepare( "SELECT leader_id FROM {$bp->activity->table_name_follow} WHERE follower_id = %d", $user_id ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the rows about to be deleted so their caches can be cleared.
	$follower_ids = $wpdb->get_col( $wpdb->prepare( "SELECT follower_id FROM {$bp->activity->table_name_follow} WHERE leader_id = %d", $user_id ) );

	bb_activity_follow_remove_data_related_users( $user_id, array_merge( (array) $leader_ids, (array) $follower_ids ) );
}
add_action( 'bp_before_remove_follow_data', 'bb_activity_follow_capture_related_users_before_remove_data' );

/**
 * Clear the follower/following caches of the other members after a user's
 * follow data has been removed.
 *
 * Mirrors `bp_activity_follow_reset_cache_incrementor()`, which clears both sides
 * of a single follow relationship, for every relationship the user had.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int        $user_id ID of the user whose follow data was removed.
 * @param array|bool $ids     Array of deleted follow IDs, or false if nothing was deleted.
 */
function bb_activity_follow_clear_related_users_cache_on_remove_data( $user_id, $ids = false ) {
	$user_ids = bb_activity_follow_remove_data_related_users( $user_id );

	if ( empty( $ids ) && empty( $user_ids ) ) {
		return;
	}

	$keys = array();
	foreach ( $user_ids as $other_id ) {
		$keys[] = 'bp_total_follower_for_user_' . $other_id;
		$keys[] = 'bp_total_following_for_user_' . $other_id;
	}

	foreach ( array_chunk( $keys, 500 ) as $chunk ) {
		wp_cache_delete_multiple( $chunk, 'bp' );
	}

	// Invalidate the cached follower/following ID lists of every member.
	bp_core_reset_incrementor( 'bp_activity_follow' );
}
add_action( 'bp_remove_follow_data', 'bb_activity_follow_clear_related_users_cache_on_remove_data', 10, 2 );

/**
 * Clear cached data for activity meta data.
 *
 * @since BuddyBoss 2.5.50
 *
 * @param int $activity_id ID of activity.
 */
function bb_activity_clear_metadata( $meta_ids, $activity_id ) {
	wp_cache_delete( $activity_id, 'activity_meta' );
}
add_action( 'deleted_activity_meta', 'bb_activity_clear_metadata', 10, 2 );
add_action( 'updated_activity_meta', 'bb_activity_clear_metadata', 10, 2 );
add_action( 'added_activity_meta', 'bb_activity_clear_metadata', 10, 2 );

/**
 * Clear cached data for activity comment counts.
 *
 * @since BuddyBoss 2.5.80
 *
 * @param array $activities Array of activities.
 */
function bb_activity_comment_reset_count( $activities ) {
	if ( ! empty( $activities ) ) {

		if ( ! is_array( $activities ) && $activities instanceof BP_Activity_Activity ) {
			$activities = array( $activities );
		}

		foreach ( $activities as $activity ) {
			// Clear the comment count cache based on its own id and parent activity ID.
			wp_cache_delete( 'bp_activity_comment_count_' . $activity->id, 'bp_activity_comments' );
			if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
				BuddyBoss\Performance\Cache::instance()->purge_by_group( 'bp-activity_' . $activity->id );
				BuddyBoss\Performance\Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( bp_activity_get_permalink( $activity->id ) ) );
			}

			// Also clear cache for all top level item.
			$comments = bb_get_activity_hierarchy( $activity->secondary_item_id );
			if ( ! empty( $comments ) ) {
				$descendants = wp_list_pluck( $comments, 'id' );
				if ( ! empty( $descendants ) ) {
					foreach ( $descendants as $activity_id ) {
						wp_cache_delete( 'bp_activity_comment_count_' . $activity_id, 'bp_activity_comments' );

						if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
							BuddyBoss\Performance\Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
							BuddyBoss\Performance\Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( bp_activity_get_permalink( $activity_id ) ) );
						}
					}
				}
			}
		}
	}
}

add_action( 'bp_activity_after_delete', 'bb_activity_comment_reset_count' );
add_action( 'bp_activity_mark_as_spam', 'bb_activity_comment_reset_count' );
add_action( 'bp_activity_mark_as_ham', 'bb_activity_comment_reset_count' );

/**
 * Reset cache incrementor for the activity when privacy is changed.
 *
 * @since BuddyBoss 2.8.20
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bb_activity_reset_cache_incrementor_on_privacy_change( $activity ) {
	// Check if the request is an ajax request and the action is activity_update_privacy.
	if (
		! wp_doing_ajax() ||
		empty( $_POST['action'] ) ||
		'activity_update_privacy' !== $_POST['action'] ||
		empty( $activity->id )
	) {
		return;
	}

	bp_activity_reset_cache_incrementor();
}
add_action( 'bp_activity_after_save', 'bb_activity_reset_cache_incrementor_on_privacy_change' );
