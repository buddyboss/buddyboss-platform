<?php
/**
 * BuddyBoss Admin Settings - Groups Callbacks.
 *
 * Sanitize callback functions for Groups feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize group layout format setting.
 *
 * Accepts only allowed layout format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized layout format value.
 */
function bb_groups_sanitize_layout_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'list_grid', 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize group layout default format setting.
 *
 * Accepts only 'grid' or 'list'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default format value.
 */
function bb_groups_sanitize_default_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize group avatar type setting.
 *
 * Accepts only allowed avatar type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized avatar type value.
 */
function bb_groups_sanitize_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'group-name', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize group cover type setting.
 *
 * Accepts only allowed cover type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover type value.
 */
function bb_groups_sanitize_cover_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'none', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize group header style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized header style value.
 */
function bb_groups_sanitize_header_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize group grid style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized grid style value.
 */
function bb_groups_sanitize_grid_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize group toggle list elements (headers, directory elements).
 *
 * Expects an associative array where keys are element slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of element_slug => 0|1.
 */
function bb_groups_sanitize_toggle_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}
