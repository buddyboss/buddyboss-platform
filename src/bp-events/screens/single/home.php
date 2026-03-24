<?php
/**
 * Single event screen handler.
 *
 * @package BuddyBoss\Events\Screens
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the single event screen.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_single_setup() {
	$event = bp_events_get_current_event();

	if ( ! $event ) {
		bp_do_404();
		return;
	}

	if ( ! $event->user_can_view() ) {
		bp_do_404();
		return;
	}

	do_action( 'bp_events_single_setup', $event );

	add_action( 'bp_template_content', 'bp_events_single_content' );
	bp_core_load_template( apply_filters( 'bp_events_single_template', 'events/single/home' ) );
}
add_action( 'bp_events_setup_theme_compat', 'bp_events_single_setup' );

/**
 * Output the single event content.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_single_content() {
	bp_get_template_part( 'events/single/home' );
}

/**
 * Get the event currently being viewed.
 *
 * @since BuddyBoss Events 1.0.0
 * @return BP_Event|false
 */
function bp_events_get_current_event() {
	$bp = buddypress();

	if ( ! empty( $bp->events->current_event ) ) {
		return $bp->events->current_event;
	}

	$slug = bp_current_item();

	if ( empty( $slug ) ) {
		return false;
	}

	global $wpdb;
	$event_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$bp->events->table_name} WHERE slug = %s LIMIT 1",
		$slug
	) );

	if ( empty( $event_id ) ) {
		return false;
	}

	$bp->events->current_event = new BP_Event( (int) $event_id );
	return $bp->events->current_event;
}
