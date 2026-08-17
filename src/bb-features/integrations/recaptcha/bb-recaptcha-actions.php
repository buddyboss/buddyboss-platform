<?php
/**
 * Recaptcha integration actions.
 *
 * @since   BuddyBoss 2.5.60
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bb_recaptcha_verification_admin_settings', 'bb_recaptcha_verification_admin_settings' );
add_action( 'login_form', 'bb_recaptcha_login', 99 );
add_filter( 'login_form_middle', 'bb_recaptcha_wp_login_form', 99 );
add_action( 'lostpassword_form', 'bb_recaptcha_lost_password' );
add_action( 'lostpassword_post', 'bb_recaptcha_validate_lost_password', 10, 1 );
add_action( 'bp_before_registration_submit_buttons', 'bb_recaptcha_registration' );
add_action( 'bp_signup_validate', 'bb_recaptcha_validate_registration' );
add_action( 'bb_before_activate_submit_buttons', 'bb_recaptcha_activate_form' );

/**
 * Handles AJAX request for reCAPTCHA verification in admin settings.
 *
 * @sicne BuddyBoss 2.5.60
 */
function bb_recaptcha_verification_admin_settings() {

	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bb-recaptcha-verification' ) ) {
		wp_send_json_error(
			array(
				'code'    => 403,
				'message' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss-platform' ),
			)
		);
	}

	// Capability check: this handler persists the site-wide reCAPTCHA settings
	// (bp_update_option 'bb_recaptcha' below), so it must be restricted to
	// administrators regardless of the nonce.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'code'    => 403,
				'message' => esc_html__( 'Sorry, you are not allowed to do that.', 'buddyboss-platform' ),
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
	$data              = '';
	if ( ! empty( $selected_version ) ) {
		if ( empty( $captcha_response ) ) {
			$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" alt="" class="recaptcha-status-icon" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss-platform' ) . '</p>';
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $captcha_response );
			if ( $response && ! empty( $response['success'] ) ) {
				$connection_status = 'connected';
				$data              = '<img src="' . bb_recaptcha_integration_url( 'assets/images/success.png' ) . '" alt="" class="recaptcha-status-icon" />
					<p>' . __( 'reCAPTCHA verification was successful', 'buddyboss-platform' ) . '</p>';
			} else {
				$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" alt="" class="recaptcha-status-icon" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss-platform' ) . '</p>';
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
 * @sicne BuddyBoss 2.5.60
 */
function bb_recaptcha_login() {
	$enable_for_login = bb_recaptcha_is_enabled( 'bb_login' );
	if ( $enable_for_login ) {
		bb_recaptcha_display( 'bb_login' );

		add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Injects the reCAPTCHA widget into front-end login forms rendered by
 * wp_login_form().
 *
 * The core `login_form` action only fires on wp-login.php, so themes/plugins that
 * embed a login form via wp_login_form() would otherwise carry no reCAPTCHA field
 * and be rejected by the (fail-closed) login validator. Hooking the
 * `login_form_middle` filter injects the same widget/token into those forms so
 * they are protected rather than broken. bb_recaptcha_display() self-gates on the
 * connection status, IP exclusion and bypass URL.
 *
 * @since BuddyBoss 3.4.2
 *
 * @param string $content Markup injected in the middle of wp_login_form().
 *
 * @return string Content with the reCAPTCHA widget appended when enabled.
 */
function bb_recaptcha_wp_login_form( $content ) {
	if (
		! bb_recaptcha_is_enabled( 'bb_login' ) ||
		'connected' !== bb_recaptcha_connection_status()
	) {
		return $content;
	}

	ob_start();
	bb_recaptcha_display( 'bb_login' );
	$widget = ob_get_clean();

	// bb_recaptcha_display() prints nothing when the widget is not actually
	// rendered (e.g. the current IP is excluded). In that case there is no
	// script to enqueue, so leave the form untouched.
	if ( '' === trim( $widget ) ) {
		return $content;
	}

	$content .= $widget;

	// Enqueue the reCAPTCHA scripts in the site footer (registered by display()).
	add_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' );

	return $content;
}

/**
 * Displays reCAPTCHA on the lost password form if enabled.
 *
 * @since BuddyBoss 2.5.60
 */
function bb_recaptcha_lost_password() {
	$enable_for_lost_password = bb_recaptcha_is_enabled( 'bb_lost_password' );
	if ( $enable_for_lost_password ) {
		bb_recaptcha_display( 'bb_lost_password' );

		add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Validate recaptcha for a lost password form.
 *
 * @since  BuddyBoss 2.5.60
 *
 * @param WP_Error $errors A WP_Error object containing any errors generated
 *                         by using invalid credentials.
 *
 * @return WP_Error $errors A WP_Error object containing any errors generated
 *                  by using invalid credentials.
 */
function bb_recaptcha_validate_lost_password( $errors ) {
	/**
	 * Filters whether reCAPTCHA validation should be bypassed for the
	 * current request. Default: true on REST requests, false otherwise.
	 *
	 * Documented here; also fires in:
	 *   - bb-recaptcha-actions.php (registration validator)
	 *   - bb-recaptcha-filters.php (login + activate validators)
	 *
	 * Each validator dispatches the filter independently so subscribers
	 * can return per-validator decisions if they want.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param bool $bypass Default: bb_is_rest().
	 */
	if ( apply_filters( 'bb_recaptcha_rest_api_bypass', bb_is_rest() ) ) {
		return $errors;
	}

	$verified = bb_recaptcha_connection_status();

	// If connection not verified and not enable for lost password then allow to reset password.
	if (
		empty( $verified ) ||
		'connected' !== $verified ||
		! bb_recaptcha_is_enabled( 'bb_lost_password' )
	) {
		return $errors;
	}

	$captcha = bb_recaptcha_verification_front( 'bb_lost_password' );
	if ( is_wp_error( $captcha ) ) {
		$errors->add( 'bb_recaptcha', $captcha->get_error_message() );
	}

	return $errors;
}

/**
 * Displays reCAPTCHA on the registration form if enabled.
 *
 * @since BuddyBoss 2.5.60
 */
function bb_recaptcha_registration() {
	$enable_for_register = bb_recaptcha_is_enabled( 'bb_register' );
	if ( $enable_for_register ) {
		do_action( 'bp_recaptcha_register_errors' );

		bb_recaptcha_display( 'bb_register' );

		add_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Validate recaptcha for a registration form.
 *
 * @since BuddyBoss 2.5.60
 */
function bb_recaptcha_validate_registration() {
	$verified = bb_recaptcha_connection_status();

	// If connection verified and enable for register, then allow to do register.
	if (
		! apply_filters( 'bb_recaptcha_rest_api_bypass', bb_is_rest() ) && // Bypass recaptcha.
		! empty( $verified ) &&
		'connected' === $verified &&
		bb_recaptcha_is_enabled( 'bb_register' )
	) {
		$captcha = bb_recaptcha_verification_front( 'bb_register' );
		if ( is_wp_error( $captcha ) ) {
			global $bp;
			$error_message                            = '<div class="bb-recaptcha-register error">' . $captcha->get_error_message() . '</div>';
			$bp->signup->errors['recaptcha_register'] = $error_message;
		}
	}
}

/**
 * Displays reCAPTCHA on the account activation form if enabled.
 *
 * @since BuddyBoss 2.5.60
 */
function bb_recaptcha_activate_form() {
	$enable_for_bb_activate = bb_recaptcha_is_enabled( 'bb_activate' );
	if ( $enable_for_bb_activate ) {
		bb_recaptcha_display( 'bb_activate' );

		add_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Enqueue scripts for recaptcha.
 *
 * @since BuddyBoss 2.5.60
 *
 * @return void
 */
function bb_recaptcha_add_scripts_login_footer() {
	wp_enqueue_script( 'bb-recaptcha' );
}
