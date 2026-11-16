<?php
/**
 * Blogs: Create screen handler
 *
 * @package BuddyBoss\Blogs\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the "Create a Blog" screen.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_blogs_screen_create_a_blog() {

	if ( ! is_multisite() || ! bboss_is_blogs_component() || ! bboss_is_current_action( 'create' ) ) {
		return false;
	}

	if ( ! is_user_logged_in() || ! bboss_blog_signup_enabled() ) {
		return false;
	}

	/**
	 * Fires right before the loading of the Create A Blog screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_blogs_screen_create_a_blog' );

	bboss_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
add_action( 'bp_screens', 'bp_blogs_screen_create_a_blog', 3 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_blogs_screen_create_a_blog' ) && ! function_exists( 'bp_blogs_screen_create_a_blog' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_blogs_screen_create_a_blog()}.
	 *
	 * @deprecated Use bboss_blogs_screen_create_a_blog() instead.
	 */
	function bp_blogs_screen_create_a_blog() {
		return bboss_blogs_screen_create_a_blog();
	}
}
// @bbwt-com-end compat-layer
