<?php
/**
 * Messages: Edit notice action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle editing of sitewide notices.
 *
 * @since BuddyPress 2.4.0 This function was split from bboss_messages_screen_notices(). See #6505.
 *
 * @return boolean
 */
function bboss_messages_action_edit_notice() {

	// Bail if not viewing a single notice URL.
	if ( ! bboss_is_messages_component() || ! bboss_is_current_action( 'notices' ) ) {
		return false;
	}

	// Get the notice ID (1|2|3).
	$notice_id = bboss_action_variable( 1 );

	// Bail if notice ID is not numeric.
	if ( empty( $notice_id ) || ! is_numeric( $notice_id ) ) {
		return false;
	}

	// Bail if the current user doesn't have administrator privileges.
	if ( ! bboss_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Get the action (deactivate|activate|delete).
	$action = sanitize_key( bboss_action_variable( 0 ) );

	// Check the nonce.
	check_admin_referer( "messages_{$action}_notice" );

	// Get the notice from database.
	$notice   = new BBoss_Messages_Notice( $notice_id );
	$success  = false;
	$feedback = '';

	// Take action.
	switch ( $action ) {

		// Deactivate.
		case 'deactivate':
			$success  = $notice->deactivate();
			$feedback = true === $success
				? __( 'Notice deactivated successfully.', 'buddyboss-platform' )
				: __( 'There was a problem deactivating that notice.', 'buddyboss-platform' );
			break;

		// Activate.
		case 'activate':
			$success  = $notice->activate();
			$feedback = true === $success
				? __( 'Notice activated successfully.', 'buddyboss-platform' )
				: __( 'There was a problem activating that notice.', 'buddyboss-platform' );
			break;

		// Delete.
		case 'delete':
			$success  = $notice->delete();
			$feedback = true === $success
				? __( 'Notice deleted successfully.', 'buddyboss-platform' )
				: __( 'There was a problem deleting that notice.', 'buddyboss-platform' );
			break;
	}

	// Feedback.
	if ( ! empty( $feedback ) ) {

		// Determine message type.
		$type = ( true === $success )
			? 'success'
			: 'error';

		// Add feedback message.
		bboss_core_add_message( $feedback, $type );
	}

	// Redirect.
	$member_notices = trailingslashit( bboss_loggedin_user_domain() . bboss_get_messages_slug() );
	$redirect_to    = trailingslashit( $member_notices . 'notices' );

	bboss_core_redirect( $redirect_to );
}
add_action( 'bp_actions', 'bboss_messages_action_edit_notice' );
