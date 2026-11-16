<?php
/**
 * Settings: export data action handler
 *
 * @package BuddyBoss\Settings\Actions
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the data export of a user.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_settings_action_export() {

	if ( ! bboss_is_post_request() ) {
		return;
	}

	if ( isset( $_POST['member-data-export-submit'] ) ) {

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'buddyboss_data_export_request' ) ) {
			wp_die( esc_html__( 'Sorry something went wrong, please try again.', 'buddyboss' ) );
		}

		if ( bboss_core_can_edit_settings() ) {

			$user_id = bboss_loggedin_user_id();

			$user       = get_userdata( $user_id );
			$request_id = wp_create_user_request( $user->data->user_email, 'export_personal_data' );

			if ( is_wp_error( $request_id ) ) {

				bboss_core_add_message( $request_id->get_error_message(), 'error' );

				// Redirect to the root domain.
				// bp_core_redirect( bp_get_root_domain() );

				return false;
			} elseif ( ! $request_id ) {

				bboss_core_add_message( __( 'Unable to initiate the data export request.', 'buddyboss' ), 'error' );

				// Redirect to the root domain.
				// bp_core_redirect( bp_get_root_domain() );

				return false;
			}

			wp_send_user_request( $request_id );

			bboss_core_add_message( __( 'Please check your email to confirm the data export request.', 'buddyboss' ), 'success' );

		}
	}
}
add_action( 'bp_actions', 'bp_settings_action_export' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_settings_action_export' ) && ! function_exists( 'bp_settings_action_export' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_settings_action_export()}.
	 *
	 * @deprecated Use bboss_settings_action_export() instead.
	 */
	function bp_settings_action_export() {
		return bboss_settings_action_export();
	}
}
// @bbwt-com-end compat-layer
