<?php
/**
 * Messages: User's "Messages > Notices" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Messages > Notices screen.
 *
 * @since BuddyPress 1.0.0
 *
 * @return false|null False on failure.
 */
function bboss_messages_screen_notices() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages notices screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'messages_screen_notices' );

	/**
	 * Filters the template to load for the Messages notices screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bboss_core_load_template( apply_filters( 'messages_template_notices', 'members/single/home' ) );
}
