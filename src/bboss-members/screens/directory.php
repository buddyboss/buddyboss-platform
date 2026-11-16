<?php
/**
 * Members: Directory screen handler
 *
 * @package BuddyBoss\Members\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of the members directory index.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_members_screen_index() {
	if ( bboss_is_members_directory() ) {
		bboss_update_is_directory( true, 'members' );

		/**
		 * Fires right before the loading of the Member directory index screen template file.
		 *
		 * @since BuddyPress 1.5.0
		 */
		do_action( 'bp_members_screen_index' );

		/**
		 * Filters the template to load for the Member directory page screen.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param string $value Path to the member directory template to load.
		 */
		bboss_core_load_template( apply_filters( 'bp_members_screen_index', 'members/index' ) );
	}
}
add_action( 'bp_screens', 'bp_members_screen_index' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_members_screen_index' ) && ! function_exists( 'bp_members_screen_index' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_members_screen_index()}.
	 *
	 * @deprecated Use bboss_members_screen_index() instead.
	 */
	function bp_members_screen_index() {
		return bboss_members_screen_index();
	}
}
// @bbwt-com-end compat-layer
