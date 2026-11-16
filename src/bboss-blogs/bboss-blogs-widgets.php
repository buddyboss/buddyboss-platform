<?php
/**
 * BuddyPress Blogs Widgets.
 *
 * @package BuddyBoss\Blogs\Widgets
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the widgets for the Blogs component.
 */
function bboss_blogs_register_widgets() {
	global $wpdb;

	if ( bboss_is_active( 'activity' ) && bboss_is_root_blog( $wpdb->blogid ) ) {
		add_action(
			'widgets_init',
			function() {
				register_widget( 'BBoss_Blogs_Recent_Posts_Widget' );
			}
		);
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_blogs_register_widgets' ) && ! function_exists( 'bp_blogs_register_widgets' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_blogs_register_widgets()}.
	 *
	 * @deprecated Use bboss_blogs_register_widgets() instead.
	 */
	function bp_blogs_register_widgets() {
		return bboss_blogs_register_widgets();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_register_widgets', 'bp_blogs_register_widgets' );
