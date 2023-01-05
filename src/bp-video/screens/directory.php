<?php
/**
 * Video: Directory screen handler
 *
 * @package BuddyBoss\Video\Screens
 * @since BuddyBoss 1.7.0
 */

/**
 * Load the Video directory.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_screen_index() {
	if ( bp_is_video_directory() ) {
		bp_update_is_directory( true, 'video' );

		/**
		 * Fires right before the loading of the Video directory screen template file.
		 *
		 * @since BuddyBoss 1.7.0
		 */
		do_action( 'bp_video_screen_index' );

		/**
		 * Filters the template to load for the Video directory screen.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param string $template Path to the video template to load.
		 */
		bp_core_load_template( apply_filters( 'bp_video_screen_index', 'video/index' ) );
	}
}
add_action( 'bp_screens', 'bp_video_screen_index' );
