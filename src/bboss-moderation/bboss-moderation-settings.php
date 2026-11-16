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
function bboss_moderation_get_setting( $option, $default = '' ) {

	// Get the option and sanitize it.
	$value = get_option( $option, $default );

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output.
	return apply_filters( 'bp_moderation_get_setting', $value, $option );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_setting' ) && ! function_exists( 'bp_moderation_get_setting' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_setting()}.
	 *
	 * @deprecated Use bboss_moderation_get_setting() instead.
	 */
	function bp_moderation_get_setting( $option, $default = '' ) {
		return bboss_moderation_get_setting( $option, $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output Moderation settings API option
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $option  Option name.
 * @param string $default Default value.
 */
function bboss_moderation_setting( $option, $default = '' ) {
	echo esc_attr( bboss_moderation_get_setting( $option, $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_setting' ) && ! function_exists( 'bp_moderation_setting' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_setting()}.
	 *
	 * @deprecated Use bboss_moderation_setting() instead.
	 */
	function bp_moderation_setting( $option, $default = '' ) {
		return bboss_moderation_setting( $option, $default );
	}
}
// @bbwt-com-end compat-layer

