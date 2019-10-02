<?php
/**
 * Connections: User's "Connections" screen handler
 *
 * @package BuddyBoss\Connections\Screens
 * @since BuddyBoss 1.0.0
 */

 // Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process the Mutual Connections page.
 *
 * @since BuddyBoss 1.0.0
 */
function friends_screen_mutual_friends() {

	/**
	 * Fires before the loading of template for the Mutual Connections page.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'friends_screen_mutual_friends' );

	/**
	 * Filters the template used to display the Mutual Connections page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $template Path to the mutual connections template to load.
	 */
	bp_core_load_template( apply_filters( 'friends_template_mutual_friends', 'members/single/home' ) );
}
