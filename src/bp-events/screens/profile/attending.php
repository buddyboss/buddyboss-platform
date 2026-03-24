<?php
/**
 * BuddyBoss Events — Profile Attending Screen.
 *
 * Screen function for /members/{username}/events/attending.
 * Called by BP_Events_Component setup_nav registration.
 *
 * @package BuddyBoss\Events\Screens
 * @since BuddyBoss Events 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Set up the attending screen.
 *
 * Hooks the template part onto bp_template_content BEFORE loading the
 * member profile wrapper template. Without the add_action call, the wrapper
 * loads but the content area is blank.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_screen_attending() {
	/**
	 * Fires before the attending screen template is loaded.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	do_action( 'bp_events_screen_attending' );

	add_action( 'bp_template_content', function() {
		bp_get_template_part( 'events/profile-attending' );
	} );

	bp_core_load_template( apply_filters( 'bp_events_template_attending', 'members/single/plugins' ) );
}
