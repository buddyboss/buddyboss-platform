<?php
/**
 * Messages: Delete action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Process a request to delete a message.
 *
 * @return bool False on failure.
 */
function bboss_messages_action_delete_message() {

	if ( ! bboss_is_messages_component() || bboss_is_current_action( 'notices' ) || ! bboss_is_action_variable( 'delete', 0 ) ) {
		return false;
	}

	$thread_id = bboss_action_variable( 1 );

	if ( ! $thread_id || ! is_numeric( $thread_id ) || ! bboss_messages_check_thread_access( $thread_id ) ) {
		bboss_core_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/' . bboss_current_action() ) );
	} else {
		if ( ! check_admin_referer( 'messages_delete_thread' ) ) {
			return false;
		}

		// Delete message.
		if ( ! bboss_messages_delete_thread( $thread_id ) ) {
			bboss_core_add_message( __( 'There was an error deleting that message.', 'buddyboss' ), 'error' );
		} else {
			bboss_core_add_message( __( 'Message deleted.', 'buddyboss' ) );
		}
		bboss_core_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/' . bboss_current_action() ) );
	}
}
add_action( 'bp_actions', 'messages_action_delete_message' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_action_delete_message' ) && ! function_exists( 'messages_action_delete_message' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_action_delete_message()}.
	 *
	 * @deprecated Use bboss_messages_action_delete_message() instead.
	 */
	function messages_action_delete_message() {
		return bboss_messages_action_delete_message();
	}
}
// @bbwt-com-end compat-layer
