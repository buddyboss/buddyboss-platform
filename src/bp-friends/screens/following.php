<?php
/**
 * Connections: User's "Following" screen handler
 *
 * @package BuddyBoss
 * @subpackage FollowingScreens
 * @since BuddyPress 3.1.1
 */

/**
 * Catch and process the My Following page.
 *
 * @since BuddyPress 3.1.1
 */
function friends_screen_my_following() {

	/**
	 * Fires before the loading of template for the My Following page.
	 *
	 * @since BuddyPress 3.1.1
	 */
	do_action( 'friends_screen_my_following' );

	/**
	 * Filters the template used to display the My Following page.
	 *
	 * @since BuddyPress 3.1.1
	 *
	 * @param string $template Path to the my following template to load.
	 */
	bp_core_load_template( apply_filters( 'friends_template_my_following', 'members/single/home' ) );
}