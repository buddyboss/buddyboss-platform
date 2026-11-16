<?php
/**
 * Blogs: User's "Sites" screen handler
 *
 * @package BuddyBoss\Blogs\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the "My Blogs" screen.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_blogs_screen_my_blogs() {
	if ( ! is_multisite() ) {
		return false;
	}

	/**
	 * Fires right before the loading of the My Blogs screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_blogs_screen_my_blogs' );

	bboss_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_blogs_screen_my_blogs' ) && ! function_exists( 'bp_blogs_screen_my_blogs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_blogs_screen_my_blogs()}.
	 *
	 * @deprecated Use bboss_blogs_screen_my_blogs() instead.
	 */
	function bp_blogs_screen_my_blogs() {
		return bboss_blogs_screen_my_blogs();
	}
}
// @bbwt-com-end compat-layer

