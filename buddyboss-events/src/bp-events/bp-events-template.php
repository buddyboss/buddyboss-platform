<?php
/**
 * BuddyBoss Events Template Tags.
 *
 * @package BuddyBoss\Events\Template
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the events directory page.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_directory() {
	bp_get_template_part( 'events/index' );
}

/**
 * Output a single event page.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_single() {
	bp_get_template_part( 'events/single/home' );
}

/**
 * Output the event creation form.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_create_form() {
	bp_get_template_part( 'events/create' );
}

/**
 * Output an events loop.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param array $args Query args passed to bp_events_get_events().
 */
function bp_events_loop( $args = array() ) {
	if ( bp_has_events( $args ) ) {
		bp_get_template_part( 'events/events-loop' );
	}
}

/**
 * Set up the events query and make it available globally.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param array $args Query args.
 * @return bool True if events found, false otherwise.
 */
function bp_has_events( $args = array() ) {
	global $events_template;

	$result = bp_events_get_events( $args );

	$events_template          = new stdClass();
	$events_template->events  = $result['events'];
	$events_template->total   = $result['total'];
	$events_template->current = -1;
	$events_template->event   = null;

	return ! empty( $events_template->events );
}

/**
 * Advance to the next event in the loop.
 *
 * @since BuddyBoss Events 1.0.0
 * @return bool
 */
function bp_events() {
	global $events_template;

	$events_template->current++;

	if ( $events_template->current < count( $events_template->events ) ) {
		$events_template->event = $events_template->events[ $events_template->current ];
		return true;
	}

	return false;
}

/**
 * Output the current event ID.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_event_id() {
	echo (int) bp_get_event_id();
}

/**
 * Get the current event ID.
 *
 * @since BuddyBoss Events 1.0.0
 * @return int
 */
function bp_get_event_id() {
	global $events_template;
	return apply_filters( 'bp_get_event_id', ! empty( $events_template->event->id ) ? $events_template->event->id : 0 );
}

/**
 * Output the current event title.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_event_title() {
	echo esc_html( bp_get_event_title() );
}

/**
 * Get the current event title.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string
 */
function bp_get_event_title() {
	global $events_template;
	return apply_filters( 'bp_get_event_title', ! empty( $events_template->event->title ) ? $events_template->event->title : '' );
}

/**
 * Output the current event permalink.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_event_permalink() {
	echo esc_url( bp_get_event_permalink_tag() );
}

/**
 * Get the current event permalink.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string
 */
function bp_get_event_permalink_tag() {
	global $events_template;
	return apply_filters( 'bp_get_event_permalink', ! empty( $events_template->event ) ? bp_get_event_permalink( $events_template->event ) : '' );
}

/**
 * Output the current event start date formatted for display.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param string $format PHP date format string.
 */
function bp_event_start_date( $format = '' ) {
	echo esc_html( bp_get_event_start_date( $format ) );
}

/**
 * Get the current event start date.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param string $format PHP date format string.
 * @return string Formatted date.
 */
function bp_get_event_start_date( $format = '' ) {
	global $events_template;

	if ( empty( $events_template->event->start_date ) ) {
		return '';
	}

	if ( empty( $format ) ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	$timestamp = strtotime( $events_template->event->start_date );
	return apply_filters( 'bp_get_event_start_date', date_i18n( $format, $timestamp ) );
}

/**
 * Output the current event type label.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_event_type_label() {
	echo esc_html( bp_get_event_type_label() );
}

/**
 * Get a human-readable label for the event type.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string
 */
function bp_get_event_type_label() {
	global $events_template;

	$types = array(
		'in-person' => __( 'In Person', 'buddyboss' ),
		'virtual'   => __( 'Virtual', 'buddyboss' ),
		'hybrid'    => __( 'Hybrid', 'buddyboss' ),
	);

	$type = ! empty( $events_template->event->type ) ? $events_template->event->type : 'in-person';
	return apply_filters( 'bp_get_event_type_label', isset( $types[ $type ] ) ? $types[ $type ] : $type );
}

/**
 * Screen function: render member attending events tab.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_screen_attending() {
	add_action( 'bp_template_content', function() {
		bp_get_template_part( 'members/single/events/attending' );
	} );
	bp_core_load_template( apply_filters( 'bp_events_screen_attending', 'members/single/home' ) );
}

/**
 * Screen function: render member hosting events tab.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_screen_hosting() {
	add_action( 'bp_template_content', function() {
		bp_get_template_part( 'members/single/events/hosting' );
	} );
	bp_core_load_template( apply_filters( 'bp_events_screen_hosting', 'members/single/home' ) );
}

/**
 * Screen function: render organizer payouts tab.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_screen_payouts() {
	add_action( 'bp_template_content', function() {
		bp_get_template_part( 'members/single/events/payouts' );
	} );
	bp_core_load_template( apply_filters( 'bp_events_screen_payouts', 'members/single/home' ) );
}

/**
 * Screen function: render my events (default tab).
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_screen_my_events() {
	bp_events_screen_attending();
}
