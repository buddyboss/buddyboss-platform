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
add_action( 'login_form', 'bb_recaptcha_login', 99 );
add_action( 'lostpassword_form', 'bb_recaptcha_lost_password' );

/**
 * Handles AJAX request for reCAPTCHA verification in admin settings.
 *
 * @sicne BuddyBoss [BBVERSION]
 */
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

/**
 * Displays the reCAPTCHA on the login form.
 *
 * @sicne BuddyBoss [BBVERSION]
 */
function bb_recaptcha_login() {
	$enable_for_login = bb_recaptcha_is_enabled( 'bb_login' );
	if ( $enable_for_login ) {
		bb_recaptcha_display( true );

		add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Displays reCAPTCHA on the lost password form if enabled.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_recaptcha_lost_password() {
	$enable_for_lost_password = bb_recaptcha_is_enabled( 'bb_lost_password' );
	if ( $enable_for_lost_password ) {
		bb_recaptcha_display( true );

		add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Enqueue scripts and localize data for reCAPTCHA in the login footer.
 * This function enqueues the necessary JavaScript file for reCAPTCHA integration and localizes data
 * to be used by the JavaScript code. The localized data includes information about the selected reCAPTCHA version,
 * site key, and actions enabled for reCAPTCHA verification.
 * For reCAPTCHA v2, additional configuration options such as the theme, size, and badge position are also included.
 *
 * @since BuddyBoss [BBVERSION]
 * @return void
 */
function bb_recaptcha_add_scripts_login_footer() {
	if ( bb_recaptcha_conflict_mode() ) {
		bb_recaptcha_remove_duplicate_scripts();
	}

	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	wp_enqueue_script(
		'bb-recaptcha',
		bb_recaptcha_integration_url( '/assets/js/bb-recaptcha' . $min . '.js' ),
		array(
			'jquery',
			'bb-recaptcha-api',
		),
		buddypress()->version
	);

	$enabled_for   = bb_recaptcha_recaptcha_versions();
	$localize_data = array(
		'selected_version' => $enabled_for,
		'site_key'         => bb_recaptcha_site_key(),
		'actions'          => bb_recaptcha_actions(),
	);
	if ( 'recaptcha_v2' === $enabled_for ) {
		$localize_data['v2_option']         = bb_recaptcha_recaptcha_v2_option();
		$localize_data['v2_theme']          = bb_recaptcha_v2_theme();
		$localize_data['v2_size']           = bb_recaptcha_v2_size();
		$localize_data['v2_badge_position'] = bb_recaptcha_v2_badge();
	}

	wp_localize_script( 'bb-recaptcha', 'bbRecaptcha', array( 'data' => $localize_data ) );
}
