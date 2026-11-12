<?php
/**
 * Notifications: User's "Notifications > Read" screen handler
 *
 * @package BuddyBoss\Notifications\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and route the 'read' notifications screen.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_notifications_screen_read() {

	/**
	 * Fires right before the loading of the notifications read screen template file.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_notifications_screen_read' );

	/**
	 * Filters the template to load for the notifications read screen.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $template Path to the notifications read template to load.
	 */
	bboss_core_load_template( apply_filters( 'bp_notifications_template_read', 'members/single/home' ) );
}

/**
 * Handle marking single notifications as unread.
 *
 * @since BuddyPress 1.9.0
 *
 * @return bool
 */
function bboss_notifications_action_mark_unread() {

	// Bail if not the read screen.
	if ( ! bboss_is_notifications_component() || ! bboss_is_current_action( 'read' ) ) {
		return false;
	}

	// Get the action.
	$action = ! empty( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	$id     = ! empty( $_GET['notification_id'] ) ? absint( $_GET['notification_id'] ) : 0;

	// Bail if no action or no ID.
	if ( ( 'unread' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and mark the notification.
	if ( bboss_verify_nonce_request( 'bp_notification_mark_unread_' . $id ) && bboss_notifications_mark_notification( $id, true ) ) {
		bboss_core_add_message( __( 'Notification successfully marked unread.', 'buddyboss' ) );
	} else {
		bboss_core_add_message( __( 'There was a problem marking that notification.', 'buddyboss' ), 'error' );
	}

	// Redirect.
	bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_notifications_slug() . '/read/' );
}
add_action( 'bp_actions', 'bp_notifications_action_mark_unread' );
