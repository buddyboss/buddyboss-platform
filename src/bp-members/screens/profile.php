<?php
/**
 * Members: Profile screen handler
 *
 * @package BuddyBoss\Members\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the display of the profile page by loading the correct template file.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_screen_display_profile() {

	/**
	 * Fires right before the loading of the Member profile screen template file.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_members_screen_display_profile' );

	/**
	 * Filters the template to load for the Member profile page screen.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $template Path to the Member template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_members_screen_display_profile', 'members/single/home' ) );
}
