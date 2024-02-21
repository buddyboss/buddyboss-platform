<?php
/**
 * Recaptcha integration filters
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Add class in body tag in the admin.
add_filter( 'admin_body_class', 'bb_admin_recaptcha_class' );
add_filter( 'wp_authenticate_user', 'bb_recaptcha_validate_login', 9999 );
add_filter( 'bb_before_core_activate_signup', 'bb_recaptcha_validate_activate' );

/**
 * Function to add class for recaptcha.
 *
 * @sicne BuddyBoss [BBVERSION]
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
 * Validate login process with reCAPTCHA if enabled.
 * If reCAPTCHA verification fails, the function returns a WP_Error object containing the error message.
 *
 * @sicne BuddyBoss [BBVERSION]
 *
 * @param WP_User|WP_Error $user WP_User or WP_Error object if a previous
 *                               callback failed authentication.
 *
 * @return WP_User|WP_Error|null WP_User object if the user is authenticated, WP_Error object on error, or null if not
 *                               authenticated.
 */
function bb_recaptcha_validate_login( $user ) {
	$verified = bb_recaptcha_connection_status();

	// If connection not verified and not enable for login then allow to login.
	if (
		empty( $verified ) ||
		'connected' !== $verified ||
		! bb_recaptcha_is_enabled( 'bb_login' )
	) {
		return $user;
	}

	// Bypass captcha for login.
	if ( bb_recaptcha_allow_bypass_enable() ) {
		$get_url_string    = bb_filter_input_string( INPUT_POST, 'bb_recaptcha_bypass' );
		$get_url_string    = ! empty( $get_url_string ) ? base64_decode( $get_url_string ) : '';
		$admin_bypass_text = bb_recaptcha_setting( 'bypass_text' );
		if ( $get_url_string === $admin_bypass_text ) {
			return $user;
		}
	}
	$captcha = bb_recaptcha_verification_front( 'bb_login' );
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $user;
}

/**
 * Validate activation process with reCAPTCHA if enabled.
 *
 * @sicne BuddyBoss [BBVERSION]
 *
 * @param bool $retval The return value to be validated.
 *
 * @return bool|WP_Error Returns the validated return value or a WP_Error object
 *                       if reCAPTCHA verification fails.
 */
function bb_recaptcha_validate_activate( $retval ) {
	$verified = bb_recaptcha_connection_status();

	// If connection not verified and not enable for activate then allow to activate.
	if (
		empty( $verified ) ||
		'connected' !== $verified ||
		! bb_recaptcha_is_enabled( 'bb_activate' )
	) {
		return $retval;
	}

	$captcha = bb_recaptcha_verification_front( 'bb_activate' );
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $retval;
}
