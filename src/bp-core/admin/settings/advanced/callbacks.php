<?php
/**
 * BuddyBoss Admin Settings - Advanced Sanitize Callbacks.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the activity load type value.
 *
 * Ensures only valid autoload types are saved.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized activity load type.
 */
function bb_advanced_sanitize_activity_load_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array_keys(
		bp_parse_args(
			apply_filters( 'bb_performance_activity_autoload', array() ),
			array(
				'infinite'  => __( 'Infinite Scroll', 'buddyboss' ),
				'load_more' => __( 'Load More', 'buddyboss' ),
			)
		)
	);

	return in_array( $value, $allowed, true ) ? $value : 'infinite';
}

/**
 * Sanitize the public content textarea values.
 *
 * Strips HTML tags and slashes, preserving newlines.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized value.
 */
function bb_advanced_sanitize_public_content( $value ) {
	return wp_strip_all_tags( stripslashes( $value ) );
}
