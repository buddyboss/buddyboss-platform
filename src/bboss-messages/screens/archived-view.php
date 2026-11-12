<?php
/**
 * Messages: User's "Messages > Archived" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyBoss 2.1.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Messages > Archived screen.
 *
 * @since BuddyBoss 2.1.4
 */
function messages_screen_archived() {

	// Bail if not viewing a single message.
	if ( ! bboss_is_messages_component() || 'view' !== bboss_action_variable( 0 ) ) {
		return false;
	}

	$thread_id   = (int) bboss_action_variable( 1 );
	$is_redirect = false;

	if ( empty( $thread_id ) || ! bboss_messages_is_valid_thread( $thread_id ) || ! messages_is_valid_archived_thread( $thread_id ) ) {
		$is_redirect = true;
	}

	// No access.
	if ( ! $is_redirect && ( ! bboss_messages_check_thread_access( $thread_id ) || ! bboss_is_my_profile() ) ) {
		// If not logged in, prompt for login.
		if ( ! is_user_logged_in() ) {
			bboss_core_no_access();
			return;

			// Redirect away.
		} else {
			bboss_core_add_message( __( 'You do not have access to that conversation.', 'buddyboss-platform' ), 'error' );
			$is_redirect = true;
		}
	}

	// Group threads cannot be opened while "Group Messages" is disabled: leave with a notice, as for other unavailable threads.
	// Straight to the latest other archived thread (or the archived list), where the archived list would send the member on.
	if ( ! $is_redirect && bboss_messages_is_disabled_group_thread( $thread_id ) ) {
		$redirect = bboss_messages_get_disabled_group_thread_redirect_url( $thread_id, true );

		bboss_core_add_message( __( 'Group messages have been disabled by a site administrator.', 'buddyboss-platform' ), 'warning' );
		bboss_core_redirect( $redirect );
	}

	if ( $is_redirect ) {
		// check if user has archived threads or not, if yes then redirect to latest archived thread.
		if ( bboss_has_message_threads( bboss_ajax_querystring( 'messages' ) . '&thread_type=archived' ) ) {
			$new_thread_id = 0;
			while ( bboss_message_threads() ) :
				bboss_message_thread();
				$new_thread_id = bboss_get_message_thread_id();
				if ( $thread_id !== $new_thread_id ) {
					break;
				}
			endwhile;

			if ( $new_thread_id ) {
				// reset error and redirect to archived thread.
				bboss_core_add_message( '', 'error' );
				wp_safe_redirect( bboss_get_message_archived_thread_view_link( $new_thread_id ) );
				exit;
			}
		} else {
			bboss_core_redirect( trailingslashit( bboss_get_messages_archived_url() ) );
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
	bboss_core_load_template( apply_filters( 'messages_template_archived', 'members/single/home' ) );
}
add_action( 'bp_screens', 'messages_screen_archived' );
