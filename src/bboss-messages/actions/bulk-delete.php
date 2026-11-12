<?php
/**
 * Messages: Bulk-delete action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Process a request to bulk delete messages.
 *
 * @return bool False on failure.
 */
function bboss_messages_action_bulk_delete() {

	if ( ! bboss_is_messages_component() || ! bboss_is_action_variable( 'bulk-delete', 0 ) ) {
		return false;
	}

	$thread_ids = $_POST['thread_ids'];

	if ( ! $thread_ids || ! bboss_messages_check_thread_access( $thread_ids ) ) {
		bboss_core_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/' . bboss_current_action() ) );
	} else {
		if ( ! check_admin_referer( 'messages_delete_thread' ) ) {
			return false;
		}

		if ( ! bboss_messages_delete_thread( $thread_ids ) ) {
			bboss_core_add_message( __( 'There was an error deleting messages.', 'buddyboss-platform' ), 'error' );
		} else {
			bboss_core_add_message( __( 'Messages deleted.', 'buddyboss-platform' ) );
		}

		bboss_core_redirect( trailingslashit( bboss_displayed_user_domain() . bboss_get_messages_slug() . '/' . bboss_current_action() ) );
	}
}
add_action( 'bp_actions', 'bboss_messages_action_bulk_delete' );
