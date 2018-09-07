<?php
/**
 * Connections: User's "Followers" screen handler
 *
 * @package BuddyBoss
 * @subpackage FollowersScreens
 * @since BuddyPress 3.1.1
 */

/**
 * Catch and process the My Followers page.
 *
 * @since BuddyPress 3.1.1
 */
function friends_screen_my_followers() {

	/**
	 * Fires before the loading of template for the My Followers page.
	 *
	 * @since BuddyPress 3.1.1
	 */
	do_action( 'friends_screen_my_followers' );

	/**
	 * Filters the template used to display the My Followers page.
	 *
	 * @since BuddyPress 3.1.1
	 *
	 * @param string $template Path to the my followers template to load.
	 */
	bp_core_load_template( apply_filters( 'friends_template_my_followers', 'members/single/home' ) );
}