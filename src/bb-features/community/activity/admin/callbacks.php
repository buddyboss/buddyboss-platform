<?php
/**
 * BuddyBoss Admin Settings - Activity Callbacks.
 *
 * Sanitize callback functions for Activity feature settings.
 *
 * @package BuddyBoss\Features\Community\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize activity edit time setting.
 *
 * Handles the toggle + select combo for activity edit and comment edit fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value.
 */
function bb_activity_sanitize_edit_time( $value ) {
	$value = intval( $value );

	// Build allowed values from bp_activity_edit_times() (seconds) plus -1 (Forever).
	$allowed = array( -1 );
	if ( function_exists( 'bp_activity_edit_times' ) ) {
		foreach ( bp_activity_edit_times() as $time ) {
			$allowed[] = intval( $time['value'] );
		}
	}

	if ( ! in_array( $value, $allowed, true ) ) {
		return 600; // Default: 10 minutes in seconds.
	}

	return $value;
}

/**
 * Sanitize activity comment edit time setting.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value.
 */
function bb_activity_sanitize_comment_edit_time( $value ) {
	$value = intval( $value );

	// Build allowed values from bp_activity_edit_times() (seconds) plus -1 (Forever).
	$allowed = array( -1 );
	if ( function_exists( 'bp_activity_edit_times' ) ) {
		foreach ( bp_activity_edit_times() as $time ) {
			$allowed[] = intval( $time['value'] );
		}
	}

	if ( ! in_array( $value, $allowed, true ) ) {
		return 600; // Default: 10 minutes in seconds.
	}

	return $value;
}

/**
 * Sanitize sharing platforms checkbox_list.
 *
 * Expects an associative array where keys are platform slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of platform_slug => 0|1.
 */
function bb_sanitize_sharing_platforms( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$allowed = array( 'messenger', 'whatsapp', 'facebook', 'twitter', 'linkedin' );
	$enabled = array();
	foreach ( $value as $key => $val ) {
		if ( in_array( $key, $allowed, true ) && absint( $val ) ) {
			$enabled[] = $key;
		}
	}

	// Save as indexed array (legacy format) so both legacy and Settings 2.0 can read it.
	return $enabled;
}

/**
 * Sanitize sortable toggle list options (activity filters, timeline filters, sorting).
 *
 * Expects an associative array where keys are option slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of option_slug => 0|1.
 */
function bb_activity_sanitize_filter_options( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}

/**
 * Sanitize comment visibility setting.
 *
 * Accepts values 0-5 for maximum comments per post.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value (0-5).
 */
function bb_activity_sanitize_comment_visibility( $value ) {
	$value = absint( $value );

	if ( $value > 5 ) {
		return 2;
	}

	return $value;
}

/**
 * Sanitize comment threading depth setting.
 *
 * Accepts values 1-4 for thread depth levels.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value (1-4).
 */
function bb_activity_sanitize_comment_threading_depth( $value ) {
	$value = absint( $value );

	if ( $value < 1 || $value > 4 ) {
		return 3;
	}

	return $value;
}

/**
 * Sanitize comment loading setting.
 *
 * Accepts specific values for number of comments to load per request.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value.
 */
function bb_activity_sanitize_comment_loading( $value ) {
	$value   = absint( $value );
	$allowed = array( 5, 10, 15, 20, 25, 30 );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 10;
	}

	return $value;
}

/**
 * Sanitize platform activity types toggle list.
 *
 * Handles the toggle_list for BuddyBoss Platform activity types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of activity_name => 0|1.
 */
function bb_activity_sanitize_platform_activity_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}

/**
 * Sanitize post type feed settings.
 *
 * Handles the toggle + checkbox combo for WordPress and custom post types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array with 'enabled' and 'comments' keys.
 */
function bb_activity_sanitize_post_type_feed( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $post_type => $settings ) {
		$clean_key = sanitize_key( $post_type );
		$sanitized[ $clean_key ] = array(
			'enabled'  => ! empty( $settings['enabled'] ) ? 1 : 0,
			'comments' => ! empty( $settings['comments'] ) ? 1 : 0,
		);
	}

	return $sanitized;
}
