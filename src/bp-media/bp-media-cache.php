<?php
/**
 * Functions related to the BuddyBoss Media component and the WP Cache.
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.1.6
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
}
add_action( 'bp_media_after_save', 'bp_media_clear_cache_for_media' );

/**
 * Clear cached data for deleted media item.
 *
 * @since BuddyBoss 1.1.5
 *
 * @param int $deleted_id IDs of deleted media item.
 */
function bp_media_clear_cache_for_deleted_media( $deleted_id ) {
	wp_cache_delete( $deleted_id, 'bp_media' );
}
add_action( 'bp_media_delete', 'bp_media_clear_cache_for_deleted_media' );

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
add_action( 'bp_media_delete',    'bp_media_reset_cache_incrementor' );
add_action( 'bp_media_add',       'bp_media_reset_cache_incrementor' );
