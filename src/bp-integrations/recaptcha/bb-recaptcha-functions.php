<?php
/**
 * Recaptcha integration helpers
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Recaptcha Integration url.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $path Path to recaptcha integration.
 */
function bb_recaptcha_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->integration_url ) . 'recaptcha/' . trim( $path, '/\\' );
}

function bb_recaptcha_score_threshold( $default = 6 ) {
	return (int) apply_filters( 'bb_recaptcha_score_threshold', bp_get_option( 'bb_recaptcha_score_threshold', $default ) );
}

function bb_recaptcha_actions() {
	$actions = array(
		'bb_login'         => array(
			'label'    => __( 'Login', 'buddyboss' ),
			'disabled' => false,
			'enabled'  => bb_recaptcha_is_enabled( 'bb_login' ),
		),
		'bb_register'      => array(
			'label'    => __( 'Registration', 'buddyboss' ),
			'disabled' => ! bp_enable_site_registration(),
			'enabled'  => bb_recaptcha_is_enabled( 'bb_register' ),
		),
		'bb_lost_password' => array(
			'label'    => __( 'Reset Password', 'buddyboss' ),
			'disabled' => false,
			'enabled'  => bb_recaptcha_is_enabled( 'bb_lost_password' ),
		),
		'bb_activate'      => array(
			'label'    => __( 'Account Activation', 'buddyboss' ),
			'disabled' => ! bp_enable_site_registration(),
			'enabled'  => bb_recaptcha_is_enabled( 'bb_activate' ),
		),
	);

	return apply_filters( 'bb_recaptcha_actions', $actions );
}

function bb_recaptcha_is_enabled( $key ) {
	$enabled_keys = bp_get_option( 'bb_recaptcha_enabled_for', array() );
	$retval = ! empty( $key ) && array_key_exists( $key, $enabled_keys ) && ! empty( $enabled_keys[ $key ] );

	return (bool) apply_filters( 'bb_recaptcha_is_enabled', $retval, $key );
}
