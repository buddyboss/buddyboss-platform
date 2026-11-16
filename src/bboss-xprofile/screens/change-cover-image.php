<?php
/**
 * XProfile: User's "Profile > Change Cover Photo" screen handler
 *
 * @package BuddyBoss\XProfileScreens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Displays the change cover photo page.
 *
 * @since BuddyPress 2.4.0
 */
function bboss_xprofile_screen_change_cover_image() {

	// Bail if not the correct screen.
	if ( ! bboss_is_my_profile() && ! bboss_current_user_can( 'bp_moderate' ) ) {
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
	bboss_core_load_template( apply_filters( 'xprofile_template_cover_image', 'members/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_xprofile_screen_change_cover_image' ) && ! function_exists( 'xprofile_screen_change_cover_image' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_xprofile_screen_change_cover_image()}.
	 *
	 * @deprecated Use bboss_xprofile_screen_change_cover_image() instead.
	 */
	function xprofile_screen_change_cover_image() {
		return bboss_xprofile_screen_change_cover_image();
	}
}
// @bbwt-com-end compat-layer
