<?php
/**
 * Functions related to the BuddyBoss Media component and the WP Cache.
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.1.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Clear a cached media item when that item is updated.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param BP_Media $media Media object.
 */
function bp_media_clear_cache_for_media( $media ) {
	wp_cache_delete( $media->id, 'bp_media' );
	wp_cache_delete( 'bp_attachment_media_id_' . $media->attachment_id, 'bp_media' );
	wp_cache_delete( 'bb_media_activity_' . $media->id, 'bp_media' ); // Used in bb_moderation_get_media_record_by_id().

	if ( ! empty( $media->activity_id ) ) {
		wp_cache_delete( 'get_activity_media_id_' . $media->activity_id, 'bp_media' );
		wp_cache_delete( 'get_activity_attachment_id_' . $media->activity_id, 'bp_media' );
	}

	if ( ! empty( $media->group_id ) ) {
		wp_cache_delete( 'total_group_media_count_' . $media->group_id, 'bp_media' );
	}

	if ( ! empty( $media->user_id ) ) {
		wp_cache_delete( 'bp_total_media_count_' . $media->user_id, 'bp_media' );
	}

}
add_action( 'bp_media_after_save', 'bp_media_clear_cache_for_media' );

/**
 * Clear cached data for deleted media items.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param array $deleted_ids IDs of deleted media items.
 */
function bp_media_clear_cache_for_deleted_media( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_media' );
		wp_cache_delete( 'bb_media_activity_' . $deleted_id, 'bp_media' ); // Used in bb_moderation_get_media_record_by_id().
	}
}
add_action( 'bp_media_deleted_medias', 'bp_media_clear_cache_for_deleted_media' );

/**
 * Reset cache incrementor for the Media component.
 *
 * Called whenever an media item is created, updated, or deleted, this
 * function effectively invalidates all cached results of media queries.
 *
 * @since BuddyBoss 1.1.5
 *
 * @return bool True on success, false on failure.
 */
function bp_media_reset_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_media' );
}
add_action( 'bp_media_delete', 'bp_media_reset_cache_incrementor' );
add_action( 'bp_media_add', 'bp_media_reset_cache_incrementor' );
add_action( 'bp_video_add', 'bp_media_reset_cache_incrementor' );

/**
 * Clear a user's cached media count.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param object $media Media object item.
 */
function bp_media_clear_media_user_object_cache( $media ) {
	$user_id       = ! empty( $media->user_id ) ? $media->user_id : false;
	$attachment_id = ! empty( $media->attachment_id ) ? $media->attachment_id : false;
	$activity_id   = ! empty( $media->activity_id ) ? $media->activity_id : false;

	if ( $user_id ) {
		wp_cache_delete( 'bp_total_media_for_user_' . $user_id, 'bp' );
		wp_cache_delete( 'bp_total_group_media_for_user_' . $user_id, 'bp' );
		wp_cache_delete( 'bp_total_media_count_' . $user_id, 'bp_media' );
		wp_cache_delete( 'total_user_group_media_count_' . $user_id, 'bp_media' );
	}
	if ( $attachment_id ) {
		wp_cache_delete( 'bp_attachment_media_id_' . $attachment_id, 'bp_media' );
	}
	if ( $activity_id ) {
		wp_cache_delete( 'get_activity_media_id_' . $activity_id, 'bp_media' );
		wp_cache_delete( 'get_activity_attachment_id_' . $activity_id, 'bp_media' );
	}
}

add_action( 'bp_media_add', 'bp_media_clear_media_user_object_cache', 10 );

/**
 * Clear a user's cached media count when delete.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param array $medias DB results of media items.
 */
function bp_media_clear_media_user_object_cache_on_delete( $medias ) {
	if ( ! empty( $medias ) ) {
		foreach ( (array) $medias as $deleted_media ) {
			$user_id       = ! empty( $deleted_media->user_id ) ? $deleted_media->user_id : false;
			$attachment_id = ! empty( $deleted_media->attachment_id ) ? $deleted_media->attachment_id : false;
			$group_id      = ! empty( $deleted_media->group_id ) ? $deleted_media->group_id : false;
			$activity_id   = ! empty( $deleted_media->activity_id ) ? $deleted_media->activity_id : false;

			wp_cache_delete( 'bb_media_activity_' . $deleted_media->id, 'bp_media' ); // Used in bb_moderation_get_media_record_by_id().

			if ( $user_id ) {
				wp_cache_delete( 'bp_total_media_for_user_' . $user_id, 'bp' );
				wp_cache_delete( 'bp_total_group_media_for_user_' . $user_id, 'bp' );
				wp_cache_delete( 'bp_total_media_count_' . $user_id, 'bp_media' );
				wp_cache_delete( 'total_user_group_media_count_' . $user_id, 'bp_media' );
			}
			if ( $attachment_id ) {
				wp_cache_delete( 'bp_attachment_media_id_' . $attachment_id, 'bp_media' );
			}

			if ( $group_id ) {
				wp_cache_delete( 'total_group_media_count_' . $group_id, 'bp_media' );
			}

			if ( $activity_id ) {
				wp_cache_delete( 'get_activity_media_id_' . $activity_id, 'bp_media' );
				wp_cache_delete( 'get_activity_attachment_id_' . $activity_id, 'bp_media' );
				wp_cache_delete( 'bp_media_activity_id_' . $activity_id, 'bp_media' );
				wp_cache_delete( 'bp_media_attachment_id_' . $activity_id, 'bp_media' );
			}
		}
	}
}

add_action( 'bp_media_before_delete', 'bp_media_clear_media_user_object_cache_on_delete', 10 );

/**
 * Clear a user's cached media count.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int $user_id ID of the user deleted.
 */
function bp_media_remove_all_user_object_cache_data( $user_id ) {
	wp_cache_delete( 'bp_total_media_for_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_total_group_media_for_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_total_media_count_' . $user_id, 'bp_media' );
	wp_cache_delete( 'total_user_group_media_count_' . $user_id, 'bp_media' );
}
add_action( 'bp_media_remove_all_user_data', 'bp_media_remove_all_user_object_cache_data' );

/**
 * Clear a group's cached media count.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param object $media Media object item.
 */
function bp_media_clear_media_group_object_cache( $media ) {
	$group_id = ! empty( $media->group_id ) ? $media->group_id : false;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_media_for_group_' . $group_id, 'bp' );
		wp_cache_delete( 'total_group_media_count_' . $group_id, 'bp_media' );
		wp_cache_delete( 'total_group_media_count_' . $group_id, 'bp_media' );
	}
}

add_action( 'bp_media_add', 'bp_media_clear_media_group_object_cache', 10 );

/**
 * Clear a group's cached media count when delete.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param array $medias DB results of media items.
 */
function bp_media_clear_media_group_object_cache_on_delete( $medias ) {
	if ( ! empty( $medias[0] ) ) {
		foreach ( (array) $medias[0] as $deleted_media ) {
			$group_id = ! empty( $deleted_media->group_id ) ? $deleted_media->group_id : false;

			if ( $group_id ) {
				wp_cache_delete( 'bp_total_media_for_group_' . $group_id, 'bp' );
			}
		}
	}
}
add_action( 'bp_media_before_delete', 'bp_media_clear_media_group_object_cache_on_delete', 10 );

/**
 * Clear a cached album item when that item is updated.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param BP_Media_Album $album Album object.
 */
function bp_media_clear_cache_for_album( $album ) {
	wp_cache_delete( $album->id, 'bp_media_album' );
}
add_action( 'bp_media_album_after_save', 'bp_media_clear_cache_for_album' );

/**
 * Clear cached data for deleted album items.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param array $deleted_ids IDs of deleted album items.
 */
function bp_media_clear_cache_for_deleted_album( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_media_album' );
	}
}
add_action( 'bp_albums_deleted_albums', 'bp_media_clear_cache_for_deleted_album' );

/**
 * Reset cache incrementor for the Album.
 *
 * Called whenever an album item is created, updated, or deleted, this
 * function effectively invalidates all cached results of album queries.
 *
 * @since BuddyBoss 1.1.5
 *
 * @return bool True on success, false on failure.
 */
function bp_media_album_reset_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_media_album' );
}
add_action( 'bp_album_delete', 'bp_media_album_reset_cache_incrementor' );
add_action( 'bp_album_add', 'bp_media_album_reset_cache_incrementor' );

/**
 * Clear a group's cached album count.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param object $album Album object item.
 */
function bp_media_clear_album_group_object_cache( $album ) {
	$group_id = ! empty( $album->group_id ) ? $album->group_id : false;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_album_for_group_' . $group_id, 'bp' );
		wp_cache_delete( 'bp_total_group_album_count_' . $group_id, 'bp_media_album' );
	}
}

add_action( 'bp_album_add', 'bp_media_clear_album_group_object_cache', 10 );

/**
 * Clear a group's cached album count when delete.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param array $albums DB results of album items.
 */
function bp_media_clear_album_group_object_cache_on_delete( $albums ) {
	if ( ! empty( $albums[0] ) ) {
		foreach ( (array) $albums[0] as $deleted_album ) {
			$group_id = ! empty( $deleted_album->group_id ) ? $deleted_album->group_id : false;

			if ( $group_id ) {
				wp_cache_delete( 'bp_total_album_for_group_' . $group_id, 'bp' );
				wp_cache_delete( 'bp_total_group_album_count_' . $group_id, 'bp_media_album' );
			}
		}
	}
}
add_action( 'bp_media_album_before_delete', 'bp_media_clear_album_group_object_cache_on_delete', 10 );
