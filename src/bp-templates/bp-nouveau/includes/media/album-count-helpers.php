<?php
/**
 * Album count helper functions
 *
 * @since BuddyBoss PROD-8677
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get album counts for both photos and videos.
 *
 * @since BuddyBoss PROD-8677
 *
 * @param int $album_id The album ID to get counts for.
 * @return array Array with 'photo_count' and 'video_count' keys.
 */
function bp_nouveau_get_album_counts( $album_id ) {
	$album_id = absint( $album_id );
	
	if ( empty( $album_id ) ) {
		return array(
			'photo_count' => 0,
			'video_count' => 0,
		);
	}

	// Get photo count
	$photo_count = 0;
	$album_media = bp_media_get( array(
		'album_id'    => $album_id,
		'count_total' => true,
	) );
	$photo_count = isset( $album_media['total'] ) ? absint( $album_media['total'] ) : 0;

	// Get video count
	$video_count = 0;
	if ( bp_is_active( 'video' ) && function_exists( 'bp_video_get' ) ) {
		$album_video = bp_video_get( array(
			'album_id'    => $album_id,
			'count_total' => true,
		) );
		$video_count = isset( $album_video['total'] ) ? absint( $album_video['total'] ) : 0;
	}

	return array(
		'photo_count' => $photo_count,
		'video_count' => $video_count,
	);
}

/**
 * Check if all media items belong to the same album.
 *
 * @since BuddyBoss PROD-8677
 *
 * @param array  $media_objects Array of BP_Media or BP_Video objects.
 * @param string $type          Type of media ('media' or 'video').
 * @return array Array with 'same_album' boolean and 'album_id' integer.
 */
function bp_nouveau_validate_same_album( $media_objects, $type = 'media' ) {
	if ( empty( $media_objects ) || ! is_array( $media_objects ) ) {
		return array(
			'same_album' => false,
			'album_id'   => 0,
		);
	}

	$first_item = $media_objects[0];
	$album_id   = ! empty( $first_item->album_id ) ? absint( $first_item->album_id ) : 0;

	if ( empty( $album_id ) ) {
		return array(
			'same_album' => false,
			'album_id'   => 0,
		);
	}

	$same_album = true;
	foreach ( $media_objects as $media_item ) {
		if ( absint( $media_item->album_id ) !== $album_id ) {
			$same_album = false;
			break;
		}
	}

	return array(
		'same_album' => $same_album,
		'album_id'   => $album_id,
	);
}

/**
 * Get album counts for media save/delete operations.
 *
 * @since BuddyBoss PROD-8677
 *
 * @param array  $media_ids Array of media IDs.
 * @param string $type      Type of media ('media' or 'video').
 * @return array Array with album count information.
 */
function bp_nouveau_get_album_counts_for_operation( $media_ids, $type = 'media' ) {
	$result = array(
		'album_id'          => 0,
		'album_photo_count' => 0,
		'album_video_count' => 0,
	);

	if ( empty( $media_ids ) || ! is_array( $media_ids ) ) {
		return $result;
	}

	// Get media objects
	$media_objects = array();
	foreach ( $media_ids as $media_id ) {
		$media_id = absint( $media_id );
		if ( $media_id > 0 ) {
			$media_obj = 'video' === $type ? new BP_Video( $media_id ) : new BP_Media( $media_id );
			if ( ! empty( $media_obj->id ) ) {
				$media_objects[] = $media_obj;
			}
		}
	}

	// Validate same album
	$validation = bp_nouveau_validate_same_album( $media_objects, $type );
	if ( ! $validation['same_album'] ) {
		return $result;
	}

	// Get counts
	$counts = bp_nouveau_get_album_counts( $validation['album_id'] );
	
	return array(
		'album_id'          => $validation['album_id'],
		'album_photo_count' => $counts['photo_count'],
		'album_video_count' => $counts['video_count'],
	);
}

/**
 * Get album counts for move operations (both source and destination).
 *
 * @since BuddyBoss PROD-8677
 *
 * @param int $source_album_id      Source album ID.
 * @param int $destination_album_id Destination album ID.
 * @return array Array with source and destination album count information.
 */
function bp_nouveau_get_move_album_counts( $source_album_id, $destination_album_id ) {
	$source_album_id      = absint( $source_album_id );
	$destination_album_id = absint( $destination_album_id );

	$result = array(
		'source_album_id'          => $source_album_id,
		'source_album_photo_count' => 0,
		'source_album_video_count' => 0,
		'album_id'                 => $destination_album_id,
		'album_photo_count'        => 0,
		'album_video_count'        => 0,
	);

	// Get source album counts
	if ( $source_album_id > 0 ) {
		$source_counts = bp_nouveau_get_album_counts( $source_album_id );
		$result['source_album_photo_count'] = $source_counts['photo_count'];
		$result['source_album_video_count'] = $source_counts['video_count'];
	}

	// Get destination album counts
	if ( $destination_album_id > 0 ) {
		$dest_counts = bp_nouveau_get_album_counts( $destination_album_id );
		$result['album_photo_count'] = $dest_counts['photo_count'];
		$result['album_video_count'] = $dest_counts['video_count'];
	}

	return $result;
}