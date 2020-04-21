<?php
/**
 * Functions related to the BuddyBoss Zoom Conference and the WP Cache.
 *
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Clear a cached zoom meeting item when that item is updated.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param BP_Zoom_Meeting $meeting Meeting object.
 */
function bp_zoom_meeting_clear_cache_for_meeting( $meeting ) {
	wp_cache_delete( $meeting->id, 'bp_meeting' );
}
add_action( 'bp_zoom_meeting_after_save', 'bp_zoom_meeting_clear_cache_for_meeting' );

/**
 * Clear cached data for deleted meeting items.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param array $deleted_ids IDs of deleted meeting items.
 */
function bp_zoom_meeting_clear_cache_for_deleted_meeting( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_meeting' );
	}
}
add_action( 'bp_zoom_meeting_deleted_meetings', 'bp_zoom_meeting_clear_cache_for_deleted_meeting' );

/**
 * Reset cache incrementor for the Zoom Meeting.
 *
 * Called whenever a meeting item is created, updated, or deleted, this
 * function effectively invalidates all cached results of meeting queries.
 *
 * @since BuddyBoss 1.2.10
 *
 * @return bool True on success, false on failure.
 */
function bp_zoom_meeting_reset_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_meeting' );
}
add_action( 'bp_zoom_meeting_delete', 'bp_zoom_meeting_reset_cache_incrementor' );
add_action( 'bp_zoom_meeting_add', 'bp_zoom_meeting_reset_cache_incrementor' );

