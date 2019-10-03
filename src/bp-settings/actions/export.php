<?php
/**
 * Settings: export data action handler
 *
 * @package BuddyBoss\Settings\Actions
 * @since BuddyBoss 1.0.0
 */

/**
 * Handles the data export of a user.
 *
 * @since BuddyPress 1.6.0
 */
function bp_settings_action_export() {

	if ( ! bp_is_post_request() ) {
		return;
	}

	if ( isset( $_POST['member-data-export-submit'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'buddyboss_data_export_request' ) ) {
			wp_die( __( 'Sorry something went wrong, please try again.', 'buddyboss' ) );
		}

		if ( bp_core_can_edit_settings() ) {

			$user_id = bp_loggedin_user_id();

			$user       = get_userdata( $user_id );
			$request_id = wp_create_user_request( $user->data->user_email, 'export_personal_data' );

			if ( is_wp_error( $request_id ) ) {

				bp_core_add_message( sprintf( __( '%s', 'buddyboss' ), $request_id->get_error_message() ), 'error' );

				// Redirect to the root domain.
				// bp_core_redirect( bp_get_root_domain() );

				return false;
			} elseif ( ! $request_id ) {

				bp_core_add_message( __( 'Unable to initiate the data export request.', 'buddyboss' ), 'error' );

				// Redirect to the root domain.
				// bp_core_redirect( bp_get_root_domain() );

				return false;
			}

			wp_send_user_request( $request_id );

			bp_core_add_message( __( 'Please check your email to confirm the data export request.', 'buddyboss' ), 'success' );

		}
	}
}
add_action( 'bp_actions', 'bp_settings_action_export' );
