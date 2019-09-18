<?php
/**
 * Media: Directory screen handler
 *
 * @package BuddyBoss\Media\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Load the Media directory.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_screen_index() {
	if ( bp_is_media_directory() ) {
		bp_update_is_directory( true, 'media' );

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
		bp_core_load_template( apply_filters( 'bp_media_screen_index', 'media/index' ) );
	}
}
add_action( 'bp_screens', 'bp_media_screen_index' );
