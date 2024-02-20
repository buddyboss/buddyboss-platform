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
add_filter( 'wp_authenticate_user', 'bb_recaptcha_login_check', 9999, 2 );
add_filter( 'lostpassword_errors', 'bb_recaptcha_lost_password_check', 10, 2 );

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
 * Checks the login credentials and performs reCAPTCHA verification if enabled.
 * If reCAPTCHA verification fails, the function returns a WP_Error object containing the error message.
 *
 * @sicne BuddyBoss [BBVERSION]
 *
 * @param WP_User|WP_Error $user      WP_User or WP_Error object if a previous
 *                                    callback failed authentication.
 * @param string           $password  Password to check against the user.
 *
 * @return WP_User|WP_Error|null WP_User object if the user is authenticated, WP_Error object on error, or null if not
 *                               authenticated.
 */
function bb_recaptcha_login_check( $user, $password ) {
	if ( ! bb_recaptcha_is_enabled( 'bb_login' ) ) {
		return $user;
	}

	// Bypass captcha for login.
	if ( bb_recaptcha_allow_bypass_enable() ) {
		$get_url_string    = bb_filter_input_string( INPUT_POST, 'bb_recaptcha_bypass' );
		$get_url_string    = ! empty( $get_url_string ) ? base64_decode( $get_url_string ) : '';
		$admin_bypass_text = bb_recaptcha_setting( 'bypass_text', '' );
		if ( $get_url_string === $admin_bypass_text ) {
			return $user;
		}
	}
	$captcha = bb_recaptcha_verification_front();
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $user;
}

/**
 * Validate recaptcha for lost password form.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_Error      $errors    A WP_Error object containing any errors generated
 *                                 by using invalid credentials.
 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
 */
function bb_recaptcha_lost_password_check( $errors, $user_data ) {
	if ( ! bb_recaptcha_is_enabled( 'bb_lost_password' ) ) {
		return $errors;
	}

	$captcha = bb_recaptcha_verification_front();
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $errors;
}
