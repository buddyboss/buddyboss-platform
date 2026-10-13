<?php
/**
 * Recaptcha integration filters.
 *
 * @since   BuddyBoss 2.5.60
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Add class in body tag in the admin.
add_filter( 'admin_body_class', 'bb_admin_recaptcha_class' );
add_filter( 'authenticate', 'bb_recaptcha_validate_login', 99999, 1 );
add_filter( 'bb_before_core_activate_signup', 'bb_recaptcha_validate_activate' );

/**
 * Function to add class for recaptcha.
 *
 * @sicne BuddyBoss 2.5.60
 *
 * @param string $classes Space-separated list of CSS classes.
 *
 * @return string
 */
function bb_admin_recaptcha_class( $classes ) {
	$current_tab = bb_filter_input_string( INPUT_GET, 'tab' );
	if ( 'bb-recaptcha' === $current_tab ) {
		$classes .= ' bb-recaptcha-settings';
	}

	return $classes;
}

/**
 * Validate a login process with reCAPTCHA if enabled.
 * If reCAPTCHA verification fails, the function returns a WP_Error object containing the error message.
 *
 * @sicne BuddyBoss 2.5.60
 *
 * @param WP_User|WP_Error $user WP_User or WP_Error object if a previous
 *                               callback failed authentication.
 *
 * @return WP_User|WP_Error|null WP_User object if the user is authenticated, WP_Error object on error, or null if not
 *                               authenticated.
 */
function bb_recaptcha_validate_login( $user ) {
	// Apply only on WordPress login page and bypass recaptcha for rest api.
	$bb_wp_login = bb_filter_input_string( INPUT_POST, 'log' );
	if (
		apply_filters( 'bb_recaptcha_rest_api_bypass', bb_is_rest() ) ||
		! $bb_wp_login
	) {
		return $user;
	}

	$verified = bb_recaptcha_connection_status();

	// If the connection is unverified and login is not enabled, proceed to bypass the captcha.
	if (
		empty( $verified ) ||
		'connected' !== $verified ||
		! bb_recaptcha_is_enabled( 'bb_login' )
	) {
		return $user;
	}

	// If the user accesses the login page using the bypass login URL, continue to bypass the captcha.
	if ( bb_recaptcha_allow_bypass_enable() ) {
		$get_url_string = bb_filter_input_string( INPUT_POST, 'bb_recaptcha_login_bypass' );
		if ( ! empty( $get_url_string ) ) {
			$get_url_string    = base64_decode( $get_url_string );
			$admin_bypass_text = bb_recaptcha_setting( 'bypass_text' );
			if ( $get_url_string === $admin_bypass_text ) {
				return $user;
			} else {
				return new WP_Error( 'authentication_failed', __( 'Invalid bypass captcha text.', 'buddyboss' ) );
			}
		}
	}

	/*
	 * Scope enforcement to endpoints BuddyBoss actually renders the widget into.
	 *
	 * A missing token cannot be forgiven on the strength of the request body: a
	 * third-party form that never had a widget and a request whose widget was
	 * stripped look identical in $_POST. Deciding from the endpoint instead keeps
	 * bb_recaptcha_verification_front() fail-closed everywhere it is reached,
	 * while leaving genuinely uncovered forms working rather than locked out.
	 */
	$protected = bb_recaptcha_is_protected_login_request();

	// Note the uncovered endpoint for developers (WP_DEBUG only).
	if ( ! $protected ) {
		bb_recaptcha_log_uncovered_login();
	}

	/**
	 * Filters whether reCAPTCHA verification should run for this login submission.
	 *
	 * By this point the request is a standard WordPress login (the `log` field is
	 * present), reCAPTCHA is connected, and it is enabled for login. The default
	 * is whether the endpoint renders the widget - see
	 * bb_recaptcha_is_protected_login_request(). Return false to skip verification
	 * for a specific custom login form that BuddyBoss does not render the widget
	 * on (e.g. a third-party front-end login that reuses the WordPress `log`/`pwd`
	 * field names). This must be a server-side decision; never key it on a
	 * client-submitted field, or the check can be bypassed.
	 *
	 * @since BuddyBoss 3.4.2
	 * @since BuddyBoss 3.4.3 Default changed from true to whether the
	 *                              endpoint renders the reCAPTCHA widget.
	 *
	 * @param bool             $verify Whether to run reCAPTCHA verification.
	 * @param WP_User|WP_Error $user   The user object from the authenticate filter.
	 */
	if ( ! apply_filters( 'bb_recaptcha_verify_login', $protected, $user ) ) {
		return $user;
	}

	// Validate the recaptcha.
	$captcha = bb_recaptcha_verification_front( 'bb_login' );
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $user;
}

/**
 * Validate the activation process with reCAPTCHA if enabled.
 *
 * @sicne BuddyBoss 2.5.60
 *
 * @param bool $retval The return value to be validated.
 *
 * @return bool|WP_Error Returns the validated return value or a WP_Error object
 *                       if reCAPTCHA verification fails.
 */
function bb_recaptcha_validate_activate( $retval ) {

	// Bypass recaptcha for rest api.
	if ( apply_filters( 'bb_recaptcha_rest_api_bypass', bb_is_rest() ) ) {
		return $retval;
	}

	// Bypass for wp-admin "Accept" bulk action, which never renders the widget.
	if ( is_admin() ) {
		return $retval;
	}

	$verified = bb_recaptcha_connection_status();

	// If the connection is unverified and activation is not enabled, proceed to bypass the captcha.
	if (
		empty( $verified ) ||
		'connected' !== $verified ||
		! bb_recaptcha_is_enabled( 'bb_activate' )
	) {
		return $retval;
	}

	// Validate the recaptcha.
	$captcha = bb_recaptcha_verification_front( 'bb_activate' );
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $retval;
}
