<?php
/**
 * Connections: User's "Connections > Requests" screen handler
 *
 * @package BuddyBoss\Connections\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process the Requests page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_friends_screen_requests() {
	if ( bboss_is_action_variable( 'accept', 0 ) && is_numeric( bboss_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_accept_friendship' );

		if ( bboss_friends_accept_friendship( bboss_action_variable( 1 ) ) ) {
			bboss_core_add_message( __( 'Connection accepted', 'buddyboss' ) );
		} else {
			bboss_core_add_message( __( 'Connection could not be accepted', 'buddyboss' ), 'error' );
		}

		bboss_core_redirect( trailingslashit( bboss_loggedin_user_domain() . bboss_current_component() . '/' . bboss_current_action() ) );

	} elseif ( bboss_is_action_variable( 'reject', 0 ) && is_numeric( bboss_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_reject_friendship' );

		if ( bboss_friends_reject_friendship( bboss_action_variable( 1 ) ) ) {
			bboss_core_add_message( __( 'Connection rejected', 'buddyboss' ) );
		} else {
			bboss_core_add_message( __( 'Connection could not be rejected', 'buddyboss' ), 'error' );
		}

		bboss_core_redirect( trailingslashit( bboss_loggedin_user_domain() . bboss_current_component() . '/' . bboss_current_action() ) );

	} elseif ( bboss_is_action_variable( 'cancel', 0 ) && is_numeric( bboss_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_withdraw_friendship' );

		if ( bboss_friends_withdraw_friendship( bboss_loggedin_user_id(), bboss_action_variable( 1 ) ) ) {
			bboss_core_add_message( __( 'Connection request withdrawn', 'buddyboss' ) );
		} else {
			bboss_core_add_message( __( 'Connection request could not be withdrawn', 'buddyboss' ), 'error' );
		}

		bboss_core_redirect( trailingslashit( bboss_loggedin_user_domain() . bboss_current_component() . '/' . bboss_current_action() ) );
	}

	/**
	 * Fires before the loading of template for the friends requests page.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'friends_screen_requests' );

	/**
	 * Filters the template used to display the My Connections page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the friends request template to load.
	 */
	bboss_core_load_template( apply_filters( 'friends_template_requests', 'members/single/home' ) );
}
