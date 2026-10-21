<?php
/**
 * Moderation Settings
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return Moderation settings API option
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $option  Option name.
 * @param string $default Default value.
 *
 * @return mixed
 * @uses  get_option()
 * @uses  esc_attr()
 * @uses  apply_filters()
 */
function bp_moderation_get_setting( $option, $default = '' ) {

	// Get the option and sanitize it.
	$value = get_option( $option, $default );

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output.
	return apply_filters( 'bp_moderation_get_setting', $value, $option );
}

/**
 * Output Moderation settings API option
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $option  Option name.
 * @param string $default Default value.
 */
function bp_moderation_setting( $option, $default = '' ) {
	echo esc_attr( bp_moderation_get_setting( $option, $default ) );
}
