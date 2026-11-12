<?php
/**
 * Messages: Unread action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle marking a single message thread as unread.
 *
 * @since BuddyPress 2.2.0
 *
 * @return false|null Returns false on failure. Otherwise redirects back to the
 *                   message box URL.
 */
function bboss_messages_action_mark_unread() {

	if ( ! bboss_is_messages_component() || bboss_is_current_action( 'notices' ) || ! bboss_is_action_variable( 'unread', 0 ) ) {
		return false;
	}

	$action = ! empty( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	$id     = ! empty( $_GET['message_id'] ) ? intval( $_GET['message_id'] ) : 0;

	// Bail if no action or no ID.
	if ( 'unread' !== $action || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce.
	if ( ! bboss_verify_nonce_request( 'bp_message_thread_mark_unread_' . $id ) ) {
		return false;
	}

	// Check access to the message and mark unread.
	if ( bboss_messages_check_thread_access( $id ) || bboss_current_user_can( 'bp_moderate' ) ) {
		bboss_messages_mark_thread_unread( $id );
		bboss_core_add_message( __( 'Message marked unread.', 'buddyboss' ) );
	} else {
		bboss_core_add_message( __( 'There was a problem marking that message.', 'buddyboss' ), 'error' );
	}

	// Redirect back to the message box URL.
	bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/' . bboss_current_action() );
}
add_action( 'bp_actions', 'bboss_messages_action_mark_unread' );
