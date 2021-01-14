<?php
/**
 * Activity: Followers screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyBoss 1.4.7
 */

/**
 * Handle the display of the followers page by loading the correct template file.
 *
 * @since BuddyBoss 1.4.7
 */
function bp_activity_screen_display_followers() {

	/**
	 * Fires right before the loading of the Member Followers screen template file.
	 *
	 * @since BuddyBoss 1.4.7
	 */
	do_action( 'bp_activity_screen_display_followers' );

	/**
	 * Filters the template to load for the Member Followers page screen.
	 *
	 * @since BuddyBoss 1.4.7
	 *
	 * @param string $template Path to the Member template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_screen_display_followers', 'members/single/home' ) );
}
