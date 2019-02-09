<?php
/**
 * Messages: User's "Messages" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Load the Messages > Inbox screen.
 *
 * @since BuddyPress 1.0.0
 */
function messages_screen_inbox() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages inbox screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'messages_screen_inbox' );

	/**
	 * Filters the template to load for the Messages inbox screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_inbox', 'members/single/home' ) );
}