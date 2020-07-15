<?php
/**
 * Activity: Following screen handler
 *
 * @package BuddyBoss\Activity\Screens
 * @since BuddyBoss 1.4.7
 */

/**
 * Handle the display of the following page by loading the correct template file.
 *
 * @since BuddyBoss 1.4.7
 */
function bp_activity_screen_display_following() {

	/**
	 * Fires right before the loading of the Member Following screen template file.
	 *
	 * @since BuddyBoss 1.4.7
	 */
	do_action( 'bp_activity_screen_display_following' );

	/**
	 * Filters the template to load for the Member Following page screen.
	 *
	 * @since BuddyBoss 1.4.7
	 *
	 * @param string $template Path to the Member template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_screen_display_following', 'members/single/home' ) );
}
