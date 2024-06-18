<?php
/**
 * Messages: View action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyBoss 2.1.4
 */

/**
 * Process a request to view a single message archived thread.
 */
function messages_action_archived() {

	// Bail if not viewing a single conversation.
	if ( ! bp_is_messages_component() || ! bp_is_current_action( 'archived' ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		bp_core_no_access();
		return;
	}

	// check if user has archived threads or not, if yes then redirect to latest archived thread.
	if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) . '&thread_type=archived' ) ) {
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
	}

	/**
	 * Fires after processing a view request for a single message archived thread.
	 *
	 * @since BuddyBoss 2.1.4
	 */
	do_action( 'messages_action_archived' );

	/**
	 * Filters the template to load for the Messages view screen.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_archived', 'members/single/home' ) );
}
add_action( 'bp_actions', 'messages_action_archived' );
