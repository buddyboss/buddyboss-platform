<?php
/**
 * BuddyPress Blogs Loader
 *
 * The blogs component tracks posts and comments to member activity feeds,
 * shows blogs the member can post to in their profiles, and caches useful
 * information from those blogs to make querying blogs in bulk more performant.
 *
 * @package BuddyBoss\Blogs\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-blogs component.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_setup_blogs() {
	bboss()->blogs = new BBoss_Blogs_Component();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_blogs' ) && ! function_exists( 'bp_setup_blogs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_blogs()}.
	 *
	 * @deprecated Use bboss_setup_blogs() instead.
	 */
	function bp_setup_blogs() {
		return bboss_setup_blogs();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_setup_components', 'bp_setup_blogs', 6 );
