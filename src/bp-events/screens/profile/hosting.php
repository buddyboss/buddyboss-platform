<?php
/**
 * BuddyBoss Events — Profile Hosting Screen.
 *
 * Screen function for /members/{username}/events/hosting.
 *
 * @package BuddyBoss\Events\Screens
 * @since BuddyBoss Events 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Set up the hosting screen.
 *
 * Hooks the template part onto bp_template_content BEFORE loading the
 * member profile wrapper template. Without the add_action call, the wrapper
 * loads but the content area is blank.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_screen_hosting() {
	/**
	 * Fires before the hosting screen template is loaded.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	do_action( 'bp_events_screen_hosting' );

	add_action( 'bp_template_content', function() {
		bp_get_template_part( 'events/profile-hosting' );
	} );

	bp_core_load_template( apply_filters( 'bp_events_template_hosting', 'members/single/plugins' ) );
}
