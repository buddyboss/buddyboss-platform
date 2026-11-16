<?php
/**
 * Connections: User's "Connections" screen handler
 *
 * @package BuddyBoss\Connections\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process the My Connections page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_friends_screen_my_friends() {

	/**
	 * Fires before the loading of template for the My Connections page.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'friends_screen_my_friends' );

	/**
	 * Filters the template used to display the My Connections page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the my friends template to load.
	 */
	bboss_core_load_template( apply_filters( 'friends_template_my_friends', 'members/single/home' ) );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_friends_screen_my_friends' ) && ! function_exists( 'friends_screen_my_friends' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_friends_screen_my_friends()}.
	 *
	 * @deprecated Use bboss_friends_screen_my_friends() instead.
	 */
	function friends_screen_my_friends() {
		return bboss_friends_screen_my_friends();
	}
}
// @bbwt-com-end compat-layer
