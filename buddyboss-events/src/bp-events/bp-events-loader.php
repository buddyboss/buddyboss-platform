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
