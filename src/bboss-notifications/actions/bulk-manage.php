<?php
/**
 * Notifications: Bulk-manage action handler
 *
 * @package BuddyBoss\Notifications\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles bulk management (mark as read/unread, delete) of notifications.
 *
 * @since BuddyPress 2.2.0
 *
 * @return bool
 */
function bboss_notifications_action_bulk_manage() {

	// Bail if not the read or unread screen.
	if ( ! bboss_is_notifications_component() || ! ( bboss_is_current_action( 'read' ) || bboss_is_current_action( 'unread' ) ) ) {
		return false;
	}

	// Get the action.
	$action        = ! empty( $_POST['notification_bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_bulk_action'] ) ) : '';
	$nonce         = ! empty( $_POST['notifications_bulk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['notifications_bulk_nonce'] ) ) : '';
	$notifications = ! empty( $_POST['notifications'] ) ? $_POST['notifications'] : '';

	// Bail if no action or no IDs.
	if ( ( ! in_array( $action, array( 'delete', 'read', 'unread' ) ) ) || empty( $notifications ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce.
	if ( ! wp_verify_nonce( $nonce, 'notifications_bulk_nonce' ) ) {
		bboss_core_add_message( __( 'There was a problem managing your notifications.', 'buddyboss-platform' ), 'error' );
		return false;
	}

	$notifications = wp_parse_id_list( $notifications );

	// Delete, mark as read or unread depending on the user 'action'.
	switch ( $action ) {
		case 'delete':
			foreach ( $notifications as $notification ) {
				bboss_notifications_delete_notification( $notification );
			}
			bboss_core_add_message( __( 'Notifications deleted.', 'buddyboss-platform' ) );
			break;

		case 'read':
			foreach ( $notifications as $notification ) {
				bboss_notifications_mark_notification( $notification, false );
			}
			bboss_core_add_message( __( 'Notifications marked as read', 'buddyboss-platform' ) );
			break;

		case 'unread':
			foreach ( $notifications as $notification ) {
				bboss_notifications_mark_notification( $notification, true );
			}
			bboss_core_add_message( __( 'Notifications marked as unread.', 'buddyboss-platform' ) );
			break;
	}

	// Redirect.
	bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_notifications_slug() . '/' . bboss_current_action() . '/' );
}
add_action( 'bp_actions', 'bboss_notifications_action_bulk_manage' );
