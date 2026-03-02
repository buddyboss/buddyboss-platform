<?php
/**
 * Media: User's "Media" screen handler
 *
 * @package BuddyBoss\Media\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Load the Media screen.
 *
 * @since BuddyPress 1.0.0
 */
function media_screen() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Media screen template file.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'media_screen' );

	/**
	 * Filters the template to load for the Media screen.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $template Path to the media template to load.
	 */
	bp_core_load_template( apply_filters( 'media_template', 'members/single/home' ) );
}
