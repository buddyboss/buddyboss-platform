<?php
/**
 * XProfile: User's "Profile > Change Cover Photo" screen handler
 *
 * @package BuddyBoss\XProfileScreens
 * @since BuddyPress 3.0.0
 */

/**
 * Displays the change cover photo page.
 *
 * @since BuddyPress 2.4.0
 */
function xprofile_screen_change_cover_image() {

	// Bail if not the correct screen.
	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	/**
	 * Fires right before the loading of the XProfile change cover photo screen template file.
	 *
	 * @since BuddyPress 2.4.0
	 */
	do_action( 'xprofile_screen_change_cover_image' );

	/**
	 * Filters the template to load for the XProfile cover photo screen.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $template Path to the XProfile cover photo template to load.
	 */
	bp_core_load_template( apply_filters( 'xprofile_template_cover_image', 'members/single/home' ) );
}
