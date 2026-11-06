<?php
/**
 * Messages: View action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyBoss 2.1.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Process a request to view a single message archived thread.
 */
function messages_action_archived() {

	// Bail if not viewing a single conversation.
	if ( ! bboss_is_messages_component() || ! bboss_is_current_action( 'archived' ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		bboss_core_no_access();
		return;
	}

	// check if user has archived threads or not, if yes then redirect to latest archived thread.
	if ( bboss_has_message_threads( bboss_ajax_querystring( 'messages' ) . '&thread_type=archived' ) ) {
		$thread_id = 0;
		while ( bboss_message_threads() ) :
			bboss_message_thread();
			$thread_id = bboss_get_message_thread_id();
			break;
		endwhile;

		if ( $thread_id ) {
			wp_safe_redirect( bboss_get_message_archived_thread_view_link( $thread_id ) );
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
	bboss_core_load_template( apply_filters( 'messages_template_archived', 'members/single/home' ) );
}
add_action( 'bp_actions', 'messages_action_archived' );
