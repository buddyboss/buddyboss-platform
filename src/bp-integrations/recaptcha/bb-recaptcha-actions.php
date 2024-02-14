<?php
/**
 * Recaptcha integration actions
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bb_recaptcha_verification', 'bb_recaptcha_verification' );

function bb_recaptcha_verification() {

	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bb-recaptcha-verification' ) ) {
		wp_send_json_error(
			array(
				'code'    => 403,
				'message' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
			)
		);
	}

	$selected_version = bb_filter_input_string( INPUT_POST, 'selected_version' );
	$site_key         = bb_filter_input_string( INPUT_POST, 'site_key' );
	$secret_key       = bb_filter_input_string( INPUT_POST, 'secret_key' );
	$captcha_response = bb_filter_input_string( INPUT_POST, 'captcha_response' );

	$connection_status = 'not_connected';
	if ( 'recaptcha_v3' === $selected_version ) {
		if ( empty( $captcha_response ) ) {
			$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
		} else {
			$response = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha_response );
			$response = json_decode( $response['body'] );

			if ( $response->success ) {
				$connection_status = 'connected';
				$data              = '<img src="' . bb_recaptcha_integration_url( 'assets/images/success.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification was successful', 'buddyboss' ) . '</p>';
			} else {
				$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
			}
		}
	}

	// Fetch settings data.
	$settings = bb_recaptcha_options();
	$settings = ! empty( $settings ) ? $settings : array();

	// Store verification data.
	$settings['recaptcha_version'] = $selected_version;
	$settings['site_key']          = $site_key;
	$settings['secret_key']        = $secret_key;
	$settings['connection_status'] = $connection_status;
	bp_update_option( 'bb_recaptcha', $settings );
	if ( 'not_connected' === $connection_status ) {
		wp_send_json_error( $data );
	}
	wp_send_json_success( $data );
	exit();
}
