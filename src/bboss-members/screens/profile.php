<?php
/**
 * Members: Profile screen handler
 *
 * @package BuddyBoss\Members\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of the profile page by loading the correct template file.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_members_screen_display_profile() {

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
	bboss_core_load_template( apply_filters( 'bp_members_screen_display_profile', 'members/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_members_screen_display_profile' ) && ! function_exists( 'bp_members_screen_display_profile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_members_screen_display_profile()}.
	 *
	 * @deprecated Use bboss_members_screen_display_profile() instead.
	 */
	function bp_members_screen_display_profile() {
		return bboss_members_screen_display_profile();
	}
}
// @bbwt-com-end compat-layer
