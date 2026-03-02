<?php
/**
 * Blogs: User's "Sites" screen handler
 *
 * @package BuddyBoss\Blogs\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the "My Blogs" screen.
 *
 * @since BuddyPress 1.0.0
 */
function bp_blogs_screen_my_blogs() {
	if ( ! is_multisite() ) {
		return false;
	}

	/**
	 * Fires right before the loading of the My Blogs screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_blogs_screen_my_blogs' );

	bp_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}
