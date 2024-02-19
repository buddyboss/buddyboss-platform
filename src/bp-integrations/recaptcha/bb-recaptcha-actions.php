<?php
/**
 * Recaptcha integration actions
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bb_recaptcha_verification_admin_settings', 'bb_recaptcha_verification_admin_settings' );

function bb_recaptcha_verification_admin_settings() {

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
	$v2_option        = bb_filter_input_string( INPUT_POST, 'v2_option' );

	// Fetch settings data.
	$settings = bb_recaptcha_options();
	$settings = ! empty( $settings ) ? $settings : array();

	$connection_status = 'not_connected';
	if (
		'recaptcha_v3' === $selected_version ||
		(
			'recaptcha_v2' === $selected_version &&
			'v2_invisible_badge' === $v2_option
		)
	) {
		if ( empty( $captcha_response ) ) {
			$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $captcha_response );
			if ( ! empty( $response ) && $response['success'] ) {
				$connection_status = 'connected';
				$data              = '<img src="' . bb_recaptcha_integration_url( 'assets/images/success.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification was successful', 'buddyboss' ) . '</p>';
			} else {
				$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
			}
		}
	} elseif ( 'recaptcha_v2' === $selected_version ) {
		if ( empty( $captcha_response ) ) {
			$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $captcha_response );
			if ( ! empty( $response ) && $response['success'] ) {
				$connection_status = 'connected';
				$data              = '<img src="' . bb_recaptcha_integration_url( 'assets/images/success.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification was successful', 'buddyboss' ) . '</p>';
			} else {
				$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
			}
		}
	}

	// Store verification data.
	$settings['recaptcha_version'] = $selected_version;
	$settings['site_key']          = $site_key;
	$settings['secret_key']        = $secret_key;
	$settings['connection_status'] = $connection_status;
	$settings['v2_option']         = $v2_option;
	bp_update_option( 'bb_recaptcha', $settings );
	if ( 'not_connected' === $connection_status ) {
		wp_send_json_error( $data );
	}
	wp_send_json_success( $data );
	exit();
}
