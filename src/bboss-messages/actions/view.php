<?php
/**
 * Messages: View action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Process a request to view a single message thread.
 */
function bboss_messages_action_conversation() {

	// Bail if not viewing a single conversation.
	if ( ! bboss_is_messages_component() || ! bboss_is_current_action( 'view' ) ) {
		return false;
	}

	// Get the thread ID from the action variable.
	$thread_id = (int) bboss_action_variable( 0 );

	if ( ! bboss_messages_is_valid_thread( $thread_id ) || ( ! bboss_messages_check_thread_access( $thread_id ) && ! bboss_current_user_can( 'bp_moderate' ) ) ) {
		return;
	}

	// Check if a new reply has been submitted.
	if ( isset( $_POST['send'] ) ) {

		// Check the nonce.
		check_admin_referer( 'messages_send_message', 'send_message_nonce' );

		// Group threads cannot be replied to while "Group Messages" is disabled, as on the AJAX reply form.
		// Inbox, or compose for members with no listed threads, so the notice is not dropped by a second redirect.
		if ( bboss_messages_is_disabled_group_thread( $thread_id ) ) {
			$redirect = bboss_messages_get_disabled_group_thread_redirect_url( $thread_id );

			bboss_core_add_message( __( 'Group messages have been disabled by a site administrator.', 'buddyboss' ), 'warning' );
			bboss_core_redirect( $redirect );
		}

		$new_reply = bboss_messages_new_message(
			array(
				'thread_id' => $thread_id,
				'subject'   => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
				'content'   => $_POST['content'],
			)
		);

		// Send the reply.
		if ( ! empty( $new_reply ) ) {
			bboss_core_add_message( __( 'Your reply was sent successfully', 'buddyboss' ) );
		} else {
			bboss_core_add_message( __( 'There was a problem sending your reply. Please try again.', 'buddyboss' ), 'error' );
		}

		bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/view/' . $thread_id . '/' );
	}

	/*
	 * Mark message read, but only run on the logged-in user's profile.
	 * If an admin visits a thread, it shouldn't change the read status.
	 */
	if ( bboss_is_my_profile() ) {
		bboss_messages_mark_thread_read( $thread_id );
	}

	/**
	 * Fires after processing a view request for a single message thread.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'messages_action_conversation' );
}
add_action( 'bp_actions', 'bboss_messages_action_conversation' );
