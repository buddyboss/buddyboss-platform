<?php
/**
 * Single event edit screen handler.
 *
 * @package BuddyBoss\Events\Screens
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the event edit screen.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_single_edit_setup() {
	if ( 'edit' !== bp_current_action() ) {
		return;
	}

	$event = bp_events_get_current_event();

	if ( ! $event || ! $event->user_can_edit() ) {
		bp_do_404();
		return;
	}

	add_action( 'bp_template_content', 'bp_events_single_edit_content' );
	bp_core_load_template( apply_filters( 'bp_events_edit_template', 'events/single/edit' ) );
}
add_action( 'bp_events_setup_theme_compat', 'bp_events_single_edit_setup' );

/**
 * Output the event edit form.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_single_edit_content() {
	bp_get_template_part( 'events/single/edit' );
}
