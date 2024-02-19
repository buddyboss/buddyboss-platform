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
