<?php
/**
 * Connections: User's "Connections" screen handler
 *
 * @package BuddyBoss\Connections\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Catch and process the My Connections page.
 *
 * @since BuddyPress 1.0.0
 */
function friends_screen_my_friends() {

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
	bp_core_load_template( apply_filters( 'friends_template_my_friends', 'members/single/home' ) );
}
