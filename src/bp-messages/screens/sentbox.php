<?php
/**
 * Messages: User's "Messages > Sent" screen handler
 *
 * @package BuddyBoss
 * @subpackage MessageScreens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the Messages > Sent screen.
 *
 * @since BuddyPress 1.0.0
 */
function messages_screen_sentbox() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages sentbox screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'messages_screen_sentbox' );

	/**
	 * Filters the template to load for the Messages sentbox screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_sentbox', 'members/single/home' ) );
}