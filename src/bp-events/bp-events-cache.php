<?php
/**
 * BuddyBoss Events Caching.
 *
 * @package BuddyBoss\Events\Cache
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register cache groups for events.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_setup_cache_groups() {
	wp_cache_add_global_groups(
		array(
			'bp_events',
			'bp_event_meta',
			'bp_event_attendees',
		)
	);
}
add_action( 'bp_setup_cache_groups', 'bp_events_setup_cache_groups' );

/**
 * Clear event cache when saved.
 *
 * @since BuddyBoss Events 1.0.0
 * @param BP_Event $event The event object.
 */
function bp_events_clear_cache_on_save( $event ) {
	wp_cache_delete( $event->id, 'bp_events' );
}
add_action( 'bp_events_after_event_save', 'bp_events_clear_cache_on_save' );

/**
 * Clear event cache when deleted.
 *
 * @since BuddyBoss Events 1.0.0
 * @param BP_Event $event The event object.
 */
function bp_events_clear_cache_on_delete( $event ) {
	wp_cache_delete( $event->id, 'bp_events' );
}
add_action( 'bp_events_before_event_delete', 'bp_events_clear_cache_on_delete' );
