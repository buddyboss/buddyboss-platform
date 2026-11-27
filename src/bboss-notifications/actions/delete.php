<?php
/**
 * Notifications: Delete action handler
 *
 * @package BuddyBoss\Notifications\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle deleting single notifications.
 *
 * @since BuddyPress 1.9.0
 *
 * @return bool
 */
function bboss_notifications_action_delete() {

	// Bail if not the read or unread screen.
	if ( ! bboss_is_notifications_component() || ! ( bboss_is_current_action( 'read' ) || bboss_is_current_action( 'unread' ) ) ) {
		return false;
	}

	// Get the action.
	$action = ! empty( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below with bp_verify_nonce_request() before anything is changed.
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below with bp_verify_nonce_request() before anything is changed.
	$id     = ! empty( $_GET['notification_id'] ) ? absint( $_GET['notification_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below with bp_verify_nonce_request() before anything is changed.

	// Bail if no action or no ID.
	if ( ( 'delete' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and delete the notification.
	if ( bboss_verify_nonce_request( 'bp_notification_delete_' . $id ) && bboss_notifications_delete_notification( $id ) ) {
		bboss_core_add_message( __( 'Notification successfully deleted.', 'buddyboss' ) );
	} else {
		bboss_core_add_message( __( 'There was a problem deleting that notification.', 'buddyboss' ), 'error' );
	}

	// Redirect.
	bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_notifications_slug() . '/' . bboss_current_action() . '/' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_notifications_action_delete' ) && ! function_exists( 'bp_notifications_action_delete' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_notifications_action_delete()}.
	 *
	 * @deprecated Use bboss_notifications_action_delete() instead.
	 */
	function bp_notifications_action_delete() {
		return bboss_notifications_action_delete();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_actions', 'bp_notifications_action_delete' );
