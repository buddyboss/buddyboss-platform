<?php
/**
 * XProfile: User's "Profile" screen handler
 *
 * @package BuddyBoss\XProfileScreens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the display of the profile page by loading the correct template file.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_xprofile_screen_display_profile() {
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
	bboss_core_load_template( apply_filters( 'xprofile_template_display_profile', 'members/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_xprofile_screen_display_profile' ) && ! function_exists( 'xprofile_screen_display_profile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_xprofile_screen_display_profile()}.
	 *
	 * @deprecated Use bboss_xprofile_screen_display_profile() instead.
	 */
	function xprofile_screen_display_profile() {
		return bboss_xprofile_screen_display_profile();
	}
}
// @bbwt-com-end compat-layer
