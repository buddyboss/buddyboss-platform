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
