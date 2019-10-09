<?php
/**
 * Messages: Star action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

/**
 * Action handler to set a message's star status for those not using JS.
 *
 * @since BuddyPress 2.3.0
 */
function bp_messages_star_action_handler() {
	if ( ! bp_is_user_messages() ) {
		return;
	}

	if ( false === ( bp_is_current_action( 'unstar' ) || bp_is_current_action( 'star' ) ) ) {
		return;
	}

	if ( ! wp_verify_nonce( bp_action_variable( 1 ), 'bp-messages-star-' . bp_action_variable( 0 ) ) ) {
		wp_die( "Oops!  That's a no-no!" );
	}

	// Check capability.
	if ( ! is_user_logged_in() || ! bp_core_can_edit_settings() ) {
		return;
	}

	// Mark the star.
	bp_messages_star_set_action(
		array(
			'action'     => bp_current_action(),
			'message_id' => bp_action_variable(),
			'bulk'       => (bool) bp_action_variable( 2 ),
		)
	);

	// Redirect back to previous screen.
	$redirect = wp_get_referer() ? wp_get_referer() : bp_displayed_user_domain() . bp_get_messages_slug();
	bp_core_redirect( $redirect );
	die();
}
add_action( 'bp_actions', 'bp_messages_star_action_handler' );
