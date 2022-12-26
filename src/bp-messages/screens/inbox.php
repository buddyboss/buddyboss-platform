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

	// check if user has threads or not, if yes then redirect to latest thread otherwise to compose screen
	if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) {
		$thread_id = 0;
		while ( bp_message_threads() ) :
			bp_message_thread();
			$thread_id = bp_get_message_thread_id();
			break;
		endwhile;

		// Redirect happening with backbone js to allow visibility of thread list on mobile screens
		/*if ( $thread_id ) {
			wp_safe_redirect( bp_get_message_thread_view_link( $thread_id ) );
			exit;
		}*/
	} else {
		wp_safe_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/compose' ) );
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
	bp_core_load_template( apply_filters( 'messages_template_inbox', 'members/single/home' ) );
}
