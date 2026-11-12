<?php
/**
 * Messages: User's "Messages" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Messages > Inbox screen.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_messages_screen_inbox() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	// check if user has threads or not, if yes then redirect to latest thread otherwise to compose screen
	if ( bboss_has_message_threads( bboss_ajax_querystring( 'messages' ) ) ) {
		$thread_id = 0;
		while ( bboss_message_threads() ) :
			bboss_message_thread();
			$thread_id = bboss_get_message_thread_id();
			break;
		endwhile;

		// Redirect happening with backbone js to allow visibility of thread list on mobile screens
		/*if ( $thread_id ) {
			wp_safe_redirect( bp_get_message_thread_view_link( $thread_id ) );
			exit;
		}*/
	} else {
		wp_safe_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/compose' ) );
		exit;
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
	bboss_core_load_template( apply_filters( 'messages_template_inbox', 'members/single/home' ) );
}
