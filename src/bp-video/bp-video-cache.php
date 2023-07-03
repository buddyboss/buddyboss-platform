<?php
/**
 * Functions related to the BuddyBoss Video component and the WP Cache.
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Clear a cached video item when that item is updated.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param BP_Video $video Video object.
 */
function bp_video_clear_cache_for_video( $video ) {
	wp_cache_delete( $video->id, 'bp_video' );
	wp_cache_delete( 'bb_video_activity_' . $video->id, 'bp_video' ); // Used in bb_moderation_get_media_record_by_id().
}
add_action( 'bp_video_after_save', 'bp_video_clear_cache_for_video' );

/**
 * Clear cached data for deleted video items.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $deleted_ids IDs of deleted video items.
 */
function bp_video_clear_cache_for_deleted_video( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_video' );
		wp_cache_delete( 'bb_video_activity_' . $deleted_id, 'bp_video' ); // Used in bb_moderation_get_media_record_by_id().
	}
}
add_action( 'bp_video_deleted_videos', 'bp_video_clear_cache_for_deleted_video' );

/**
 * Reset cache incrementor for the Video component.
 *
 * Called whenever an video item is created, updated, or deleted, this
 * function effectively invalidates all cached results of video queries.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return bool True on success, false on failure.
 */
function bp_video_reset_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_video' );
}
add_action( 'bp_video_delete', 'bp_video_reset_cache_incrementor' );
add_action( 'bp_video_add', 'bp_video_reset_cache_incrementor' );

/**
 * Clear a user's cached video count.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param object $video Video object item.
 */
function bp_video_clear_video_user_object_cache( $video ) {
	$user_id = ! empty( $video->user_id ) ? $video->user_id : false;

	if ( $user_id ) {
		wp_cache_delete( 'bp_total_video_for_user_' . $user_id, 'bp' );
		wp_cache_delete( 'bp_total_group_video_for_user_' . $user_id, 'bp' );
	}
}
add_action( 'bp_video_add', 'bp_video_clear_video_user_object_cache', 10 );

/**
 * Clear a user's cached video count when delete.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $videos DB results of video items.
 */
function bp_video_clear_video_user_object_cache_on_delete( $videos ) {
	if ( ! empty( $videos ) ) {
		foreach ( (array) $videos as $deleted_video ) {
			$user_id = ! empty( $deleted_video->user_id ) ? $deleted_video->user_id : false;

			wp_cache_delete( 'bb_video_activity_' . $deleted_video->id, 'bp_video' ); // Used in bb_moderation_get_media_record_by_id().

			if ( ! empty( $deleted_video->activity_id ) ) {
				wp_cache_delete( 'bp_video_activity_id_' . $deleted_video->activity_id, 'bp_video' );
				wp_cache_delete( 'bp_video_attachment_id_' . $deleted_video->activity_id, 'bp_video' );
			}

			if ( $user_id ) {
				wp_cache_delete( 'bp_total_video_for_user_' . $user_id, 'bp' );
				wp_cache_delete( 'bp_total_group_video_for_user_' . $user_id, 'bp' );
			}
		}
	}
}
add_action( 'bp_video_before_delete', 'bp_video_clear_video_user_object_cache_on_delete', 10 );

/**
 * Clear a user's cached video count.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $user_id ID of the user deleted.
 */
function bp_video_remove_all_user_object_cache_data( $user_id ) {
	wp_cache_delete( 'bp_total_video_for_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_total_group_video_for_user_' . $user_id, 'bp' );
}
add_action( 'bp_video_remove_all_user_data', 'bp_video_remove_all_user_object_cache_data' );

/**
 * Clear a group's cached video count.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param object $video Video object item.
 */
function bp_video_clear_video_group_object_cache( $video ) {
	$group_id = ! empty( $video->group_id ) ? $video->group_id : false;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_video_for_group_' . $group_id, 'bp' );
	}
}
add_action( 'bp_video_add', 'bp_video_clear_video_group_object_cache', 10 );

/**
 * Clear a group's cached video count when delete.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $videos DB results of video items.
 */
function bp_video_clear_video_group_object_cache_on_delete( $videos ) {
	if ( ! empty( $videos[0] ) ) {
		foreach ( (array) $videos[0] as $deleted_video ) {
			$group_id = ! empty( $deleted_video->group_id ) ? $deleted_video->group_id : false;

			if ( $group_id ) {
				wp_cache_delete( 'bp_total_video_for_group_' . $group_id, 'bp' );
			}
		}
	}
}
add_action( 'bp_video_before_delete', 'bp_video_clear_video_group_object_cache_on_delete', 10 );

/**
 * Clear a cached album item when that item is updated.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param BP_Video_Album $album Album object.
 */
function bp_video_clear_cache_for_album( $album ) {
	wp_cache_delete( $album->id, 'bp_video_album' );
	wp_cache_delete( 'bp_video_user_video_album_' . $album->user_id . '_' . $album->group_id, 'bp_video_album' );
}
add_action( 'bp_video_album_after_save', 'bp_video_clear_cache_for_album' );

/**
 * Clear cached data for deleted album items.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $deleted_ids IDs of deleted album items.
 */
function bp_video_clear_cache_for_deleted_album( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_video_album' );
	}
}
add_action( 'bp_video_albums_deleted_albums', 'bp_video_clear_cache_for_deleted_album' );

/**
 * Reset cache incrementor for the Album.
 *
 * Called whenever an album item is created, updated, or deleted, this
 * function effectively invalidates all cached results of album queries.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return bool True on success, false on failure.
 */
function bp_video_album_reset_cache_incrementor() {
	bp_core_reset_incrementor( 'bp_media_album' );
	return bp_core_reset_incrementor( 'bp_video_album' );
}
add_action( 'bp_video_album_delete', 'bp_video_album_reset_cache_incrementor' );
add_action( 'bp_video_album_add', 'bp_video_album_reset_cache_incrementor' );

/**
 * Clear a group's cached album count.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param object $album Album object item.
 */
function bp_video_clear_album_group_object_cache( $album ) {
	$group_id = ! empty( $album->group_id ) ? $album->group_id : 0;
	$user_id  = ! empty( $album->user_id ) ? $album->user_id : 0;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_album_for_group_' . $group_id, 'bp' );
	}

	wp_cache_delete( 'bp_video_user_video_album_' . $user_id . '_' . $group_id, 'bp_video_album' );

}
add_action( 'bp_video_album_add', 'bp_video_clear_album_group_object_cache', 10 );

/**
 * Clear a group's cached album count when delete.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $albums DB results of album items.
 */
function bp_video_clear_album_group_object_cache_on_delete( $albums ) {
	if ( ! empty( $albums[0] ) ) {
		foreach ( (array) $albums[0] as $deleted_album ) {
			$group_id = ! empty( $deleted_album->group_id ) ? $deleted_album->group_id : 0;
			$user_id  = ! empty( $deleted_album->user_id ) ? $deleted_album->user_id : 0;

			if ( $group_id ) {
				wp_cache_delete( 'bp_total_album_for_group_' . $group_id, 'bp' );
			}

			wp_cache_delete( 'bp_video_user_video_album_' . $user_id . '_' . $group_id, 'bp_video_album' );

		}
	}
}
add_action( 'bp_video_album_before_delete', 'bp_video_clear_album_group_object_cache_on_delete', 10 );
