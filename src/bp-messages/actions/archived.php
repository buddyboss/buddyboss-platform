<?php
/**
 * Messages: View action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

/**
 * Process a request to view a single message thread.
 */
function messages_action_archived() {

	// Bail if not viewing a single conversation.
	if ( ! bp_is_messages_component() || ! bp_is_current_action( 'archived' ) ) {
		return false;
	}

	// check if user has threads or not, if yes then redirect to latest thread otherwise to compose screen
	if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) {
		$thread_id = 0;
		while ( bp_message_threads() ) :
			bp_message_thread();
			$thread_id = bp_get_message_thread_id();
			break;
		endwhile;

		if ( $thread_id ) {
			wp_safe_redirect( bb_get_message_archived_thread_view_link( $thread_id ) );
			exit;
		}
	} else {
		wp_safe_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/compose' ) );
		exit;
	}

	/**
	 * Fires after processing a view request for a single message thread.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'messages_action_archived' );
}
add_action( 'bp_actions', 'messages_action_archived' );
