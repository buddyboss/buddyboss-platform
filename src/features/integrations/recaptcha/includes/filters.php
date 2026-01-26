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
