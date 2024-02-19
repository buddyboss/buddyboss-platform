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
add_action( 'authenticate', 'bb_recaptcha_login_check', 9999, 3 );

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
 * @param WP_User|WP_Error|null $user     WP_User object if the user is authenticated, WP_Error object on error, or
 *                                        null if not authenticated.
 * @param string                $username The username submitted during login.
 * @param string                $password The password submitted during login.
 *
 * @return WP_User|WP_Error|null WP_User object if the user is authenticated, WP_Error object on error, or null if not
 *                               authenticated.
 */
function bb_recaptcha_login_check( $user, $username, $password ) {
	if ( ! $username ) {
		return $user;
	}
	$captcha = bb_recaptcha_verification_front();
	if ( is_wp_error( $captcha ) ) {
		return $captcha;
	}

	return $user;
}
