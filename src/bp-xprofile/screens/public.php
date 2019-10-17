<?php
/**
 * XProfile: User's "Profile" screen handler
 *
 * @package BuddyBoss\XProfileScreens
 * @since BuddyPress 3.0.0
 */

/**
 * Handles the display of the profile page by loading the correct template file.
 *
 * @since BuddyPress 1.0.0
 */
function xprofile_screen_display_profile() {
	$new = isset( $_GET['new'] ) ? $_GET['new'] : '';

	/**
	 * Fires right before the loading of the XProfile screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $new $_GET parameter holding the "new" parameter.
	 */
	do_action( 'xprofile_screen_display_profile', $new );

	/**
	 * Filters the template to load for the XProfile screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the XProfile template to load.
	 */
	bp_core_load_template( apply_filters( 'xprofile_template_display_profile', 'members/single/home' ) );
}
