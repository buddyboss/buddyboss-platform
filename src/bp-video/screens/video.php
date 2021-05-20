<?php
/**
 * Video: User's "Video" screen handler
 *
 * @package BuddyBoss\Video\Screens
 * @since BuddyBoss 1.7.0
 */

/**
 * Load the Video screen.
 *
 * @since BuddyBoss 1.7.0
 */
function video_screen() {

	if ( bp_action_variables() ) {
		bp_do_404();

		return;
	}

	/**
	 * Fires right before the loading of the Video screen template file.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'video_screen' );

	/**
	 * Filters the template to load for the Video screen.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $template Path to the video template to load.
	 */
	bp_core_load_template( apply_filters( 'video_template', 'members/single/home' ) );
}
