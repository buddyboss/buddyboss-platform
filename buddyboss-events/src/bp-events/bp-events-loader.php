<?php
/**
 * BuddyBoss Events Loader.
 *
 * A native events component for BuddyBoss Platform. Integrates with groups,
 * activity feeds, member profiles, and supports paid ticketing via Stripe Connect.
 *
 * @package BuddyBoss\Events\Loader
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load the moderation integration class.
require_once __DIR__ . '/classes/class-bp-moderation-events.php';

/**
 * Set up the bp-events component.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_setup_events() {
	buddypress()->events = new BP_Events_Component();
}
add_action( 'bp_setup_components', 'bp_setup_events', 9 );

/**
 * Enqueue assets for the event creation wizard (/events/create).
 *
 * Loads bp-events-create.js only on the create screen and localises the
 * bpEventsCreate object so the wizard can POST to the REST API.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_enqueue_create_assets() {
	if ( ! bp_is_current_component( 'events' ) ) {
		return;
	}

	// Only load on the create screen.
	if ( ! bp_is_action_variable( 'create', 0 ) ) {
		return;
	}

	wp_enqueue_script(
		'bp-events-create',
		plugins_url( 'src/bp-events/assets/js/bp-events-create.js', BP_EVENTS_PLUGIN_FILE ),
		array( 'jquery' ),
		BP_PLATFORM_VERSION,
		true
	);

	wp_localize_script(
		'bp-events-create',
		'bpEventsCreate',
		array(
			'restUrl' => rest_url( 'buddyboss/v1/events' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bp_events_enqueue_create_assets' );

/**
 * Enqueue assets for the single event page (/events/{slug}).
 *
 * Loads bp-events-single.js only on single event pages and localises
 * bpEventsSingle with pre-computed RSVP state to avoid in-page
 * state-mismatch on reload.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_enqueue_single_assets() {
	if ( ! bp_is_current_component( 'events' ) || ! bp_is_single_item() ) {
		return;
	}

	$event = bp_events_get_current_event();
	if ( ! $event ) {
		return;
	}

	// Pre-compute RSVP state for JS initialisation.
	$current_user_id = bp_loggedin_user_id();
	$attendee_ids    = wp_list_pluck( bp_events_get_attendees( $event->id, 'registered' ), 'user_id' );
	$waitlisted_ids  = wp_list_pluck( bp_events_get_waitlist( $event->id ), 'user_id' );
	$is_attending    = $current_user_id && in_array( $current_user_id, $attendee_ids, true );
	$is_waitlisted   = $current_user_id && in_array( $current_user_id, $waitlisted_ids, true );
	$at_capacity     = ! is_null( $event->capacity ) && count( $attendee_ids ) >= (int) $event->capacity;
	$can_rsvp        = true;
	$restricted_msg  = '';

	if ( $current_user_id ) {
		$rsvp_check = bp_events_user_can_rsvp( $event->id, $current_user_id );
		if ( is_wp_error( $rsvp_check ) ) {
			$can_rsvp       = false;
			$restricted_msg = $rsvp_check->get_error_message();
		}
	}

	wp_enqueue_script(
		'bp-events-single',
		plugins_url( 'src/bp-events/assets/js/bp-events-single.js', BP_EVENTS_PLUGIN_FILE ),
		array( 'jquery' ),
		BP_PLATFORM_VERSION,
		true
	);

	$localize_data = array(
		'restUrl'       => rest_url( 'buddyboss/v1/events' ),
		'nonce'         => wp_create_nonce( 'wp_rest' ),
		'eventId'       => (int) $event->id,
		'currentUserId' => $current_user_id,
		'isAttending'   => $is_attending,
		'isWaitlisted'  => $is_waitlisted,
		'atCapacity'    => $at_capacity,
		'canRsvp'       => $can_rsvp,
		'restrictedMsg' => $restricted_msg,
		'i18n'          => array(
			'rsvp'          => __( 'RSVP', 'buddyboss' ),
			'attending'     => __( 'Attending &#x2713;', 'buddyboss' ),
			'onWaitlist'    => __( 'On Waitlist', 'buddyboss' ),
			'joinWaitlist'  => __( 'Join Waitlist', 'buddyboss' ),
			'errorRsvp'     => __( 'Could not complete your RSVP. Please try again.', 'buddyboss' ),
			'errorCancel'   => __( 'Could not cancel your RSVP. Please try again.', 'buddyboss' ),
			'errorGcal'     => __( 'Could not open Google Calendar. Please try again.', 'buddyboss' ),
			'confirmRemove' => __( 'Remove this attendee from the event?', 'buddyboss' ),
		),
	);

	// On the edit screen, pass group context so the invite panel JS can fetch
	// the group member roster and POST invite selections.
	if ( bp_is_action_variable( 'edit', 0 ) && ! empty( $event->group_id ) ) {
		$localize_data['groupsRestUrl'] = rest_url( 'buddyboss/v1/groups' );
		$localize_data['groupId']       = (int) $event->group_id;
	}

	wp_localize_script( 'bp-events-single', 'bpEventsSingle', $localize_data );
}
add_action( 'wp_enqueue_scripts', 'bp_events_enqueue_single_assets' );

/**
 * Schedule the daily occurrence-extension cron job on plugin activation.
 *
 * Fires on the 'bp_events_activated' action, which is triggered by the
 * BuddyBoss Platform activation hook.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_schedule_cron() {
	if ( ! wp_next_scheduled( 'bp_events_extend_occurrences' ) ) {
		wp_schedule_event( time(), 'daily', 'bp_events_extend_occurrences' );
	}
}
add_action( 'bp_events_activated', 'bp_events_schedule_cron' );
