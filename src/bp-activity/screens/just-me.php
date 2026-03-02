<?php
/**
 * Activity: User's "Activity" screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the 'My Activity' page.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_screen_my_activity() {

	/**
	 * Fires right before the loading of the "My Activity" screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_activity_screen_my_activity' );

	/**
	 * Filters the template to load for the "My Activity" screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'members/single/home' ) );
}
