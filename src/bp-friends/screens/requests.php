<?php
/**
 * Connections: User's "Connections > Requests" screen handler
 *
 * @package BuddyBoss\Connections\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Catch and process the Requests page.
 *
 * @since BuddyPress 1.0.0
 */
function friends_screen_requests() {
	if ( bp_is_action_variable( 'accept', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_accept_friendship' );

		if ( friends_accept_friendship( bp_action_variable( 1 ) ) ) {
			bp_core_add_message( __( 'Connection accepted', 'buddyboss' ) );
		} else {
			bp_core_add_message( __( 'Connection could not be accepted', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_current_component() . '/' . bp_current_action() ) );

	} elseif ( bp_is_action_variable( 'reject', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_reject_friendship' );

		if ( friends_reject_friendship( bp_action_variable( 1 ) ) ) {
			bp_core_add_message( __( 'Connection rejected', 'buddyboss' ) );
		} else {
			bp_core_add_message( __( 'Connection could not be rejected', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_current_component() . '/' . bp_current_action() ) );

	} elseif ( bp_is_action_variable( 'cancel', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce.
		check_admin_referer( 'friends_withdraw_friendship' );

		if ( friends_withdraw_friendship( bp_loggedin_user_id(), bp_action_variable( 1 ) ) ) {
			bp_core_add_message( __( 'Connection request withdrawn', 'buddyboss' ) );
		} else {
			bp_core_add_message( __( 'Connection request could not be withdrawn', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_current_component() . '/' . bp_current_action() ) );
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
	bp_core_load_template( apply_filters( 'friends_template_requests', 'members/single/home' ) );
}
