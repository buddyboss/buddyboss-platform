<?php
/**
 * Messages: Star action handler
 *
 * @package BuddyBoss\Message\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Action handler to set a message's star status for those not using JS.
 *
 * @since BuddyPress 2.3.0
 */
function bboss_messages_star_action_handler() {
	if ( ! bboss_is_user_messages() ) {
		return;
	}

	if ( false === ( bboss_is_current_action( 'unstar' ) || bboss_is_current_action( 'star' ) ) ) {
		return;
	}

	if ( ! wp_verify_nonce( bboss_action_variable( 1 ), 'bp-messages-star-' . bboss_action_variable( 0 ) ) ) {
		wp_die( "Oops!  That's a no-no!" );
	}

	// Check capability.
	if ( ! is_user_logged_in() || ! bboss_core_can_edit_settings() ) {
		return;
	}

	// Mark the star.
	bboss_messages_star_set_action(
		array(
			'action'     => bboss_current_action(),
			'message_id' => bboss_action_variable(),
			'bulk'       => (bool) bboss_action_variable( 2 ),
		)
	);

	// Redirect back to previous screen.
	$redirect = wp_get_referer() ? wp_get_referer() : bboss_displayed_user_domain() . bboss_get_messages_slug();
	bboss_core_redirect( $redirect );
	die();
}
add_action( 'bp_actions', 'bp_messages_star_action_handler' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_star_action_handler' ) && ! function_exists( 'bp_messages_star_action_handler' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_star_action_handler()}.
	 *
	 * @deprecated Use bboss_messages_star_action_handler() instead.
	 */
	function bp_messages_star_action_handler() {
		return bboss_messages_star_action_handler();
	}
}
// @bbwt-com-end compat-layer
