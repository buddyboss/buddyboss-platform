<?php
/**
 * Blogs: Directory screen handler
 *
 * @package BuddyBoss\Blogs\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the top-level Blogs directory.
 *
 * @since BuddyPress 1.5-beta-1
 */
function bp_blogs_screen_index() {
	if ( bp_is_blogs_directory() ) {
		bp_update_is_directory( true, 'blogs' );

		/**
		 * Fires right before the loading of the top-level Blogs screen template file.
		 *
		 * @since BuddyPress 1.0.0
		 */
		do_action( 'bp_blogs_screen_index' );

		bp_core_load_template( apply_filters( 'bp_blogs_screen_index', 'blogs/index' ) );
	}
}
add_action( 'bp_screens', 'bp_blogs_screen_index', 2 );
