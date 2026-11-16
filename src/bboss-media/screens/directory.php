<?php
/**
 * Media: Directory screen handler
 *
 * @package BuddyBoss\Media\Screens
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Media directory.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_media_screen_index() {
	if ( bboss_is_media_directory() ) {
		bboss_update_is_directory( true, 'media' );

		/**
		 * Fires right before the loading of the Media directory screen template file.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_media_screen_index' );

		/**
		 * Filters the template to load for the Media directory screen.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $template Path to the media template to load.
		 */
		bboss_core_load_template( apply_filters( 'bp_media_screen_index', 'media/index' ) );
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_media_screen_index' ) && ! function_exists( 'bp_media_screen_index' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_media_screen_index()}.
	 *
	 * @deprecated Use bboss_media_screen_index() instead.
	 */
	function bp_media_screen_index() {
		return bboss_media_screen_index();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_screens', 'bp_media_screen_index' );
