<?php
/**
 * Messages: User's "Messages > Archived" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyBoss 2.1.4
 */

/**
 * Load the Messages > Archived screen.
 *
 * @since BuddyBoss 2.1.4
 */
function messages_screen_archived() {

	// Bail if not viewing a single message.
	if ( ! bp_is_messages_component() || 'view' !== bp_action_variable( 0 ) ) {
		return false;
	}

	$thread_id   = (int) bp_action_variable( 1 );
	$is_redirect = false;

	if ( empty( $thread_id ) || ! messages_is_valid_thread( $thread_id ) || ! messages_is_valid_archived_thread( $thread_id ) ) {
		$is_redirect = true;
	}

	// No access.
	if ( ! $is_redirect && ( ! messages_check_thread_access( $thread_id ) || ! bp_is_my_profile() ) ) {
		// If not logged in, prompt for login.
		if ( ! is_user_logged_in() ) {
			bp_core_no_access();
			return;

			// Redirect away.
		} else {
			bp_core_add_message( __( 'You do not have access to that conversation.', 'buddyboss' ), 'error' );
			$is_redirect = true;
		}
	}

	if ( $is_redirect ) {
		// check if user has archived threads or not, if yes then redirect to latest archived thread.
		if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) . '&thread_type=archived' ) ) {
			$new_thread_id = 0;
			while ( bp_message_threads() ) :
				bp_message_thread();
				$new_thread_id = bp_get_message_thread_id();
				if ( $thread_id !== $new_thread_id ) {
					break;
				}
			endwhile;

			if ( $new_thread_id ) {
				// reset error and redirect to archived thread.
				bp_core_add_message( '', 'error' );
				wp_safe_redirect( bb_get_message_archived_thread_view_link( $new_thread_id ) );
				exit;
			}
		} else {
			bp_core_redirect( trailingslashit( bb_get_messages_archived_url() ) );
		}
	}

	/**
	 * Fires right before the loading of the Messages view screen template file.
	 *
	 * @since BuddyBoss 2.1.4
	 */
	do_action( 'messages_screen_archived' );

	/**
	 * Filters the template to load for the Messages view screen.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_archived', 'members/single/home' ) );
}
add_action( 'bp_screens', 'messages_screen_archived' );
