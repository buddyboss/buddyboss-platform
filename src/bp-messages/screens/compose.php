<?php
/**
 * Messages: User's "Messages > Compose" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Messages > Compose screen.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_messages_screen_compose() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	// Remove any saved message data from a previous session.
	bboss_messages_remove_callback_values();

	/**
	 * Fires right before the loading of the Messages compose screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'messages_screen_compose' );

	/**
	 * Filters the template to load for the Messages compose screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bboss_core_load_template( apply_filters( 'messages_template_compose', 'members/single/home' ) );
}
