<?php
/**
 * BuddyBoss Admin Settings - Media Callbacks.
 *
 * Sanitize callback functions for Media feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Build a human-readable description from a prefix and a list of context words.
 *
 * Joins the list with commas and "and" before the last item to produce strings
 * like "Allow members to upload photos in groups, activity posts, messages and forums".
 *
 * Used by Photos, Videos, Documents, Emoji, and GIFs panels to dynamically
 * describe where uploading is enabled based on active components.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $prefix   The opening text (e.g., "Allow members to upload photos in").
 * @param array  $contexts List of context words (e.g., array( 'groups', 'activity posts' )).
 *
 * @return string The formatted description string.
 */
function bb_media_build_context_description( $prefix, $contexts ) {
	if ( empty( $contexts ) ) {
		return $prefix;
	}

	$last = array_pop( $contexts );

	if ( count( $contexts ) > 0 ) {
		return $prefix . ' ' . implode( ', ', $contexts ) . ' and ' . $last;
	}

	return $prefix . ' ' . $last;
}

/**
 * Sanitize upload size fields.
 *
 * Ensures the value is a positive integer that does not exceed the server's max upload size.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized upload size in MB.
 */
function bb_media_sanitize_upload_size( $value ) {
	$value = absint( $value );

	// Get server max upload size in MB.
	if ( function_exists( 'bp_media_format_size_units' ) ) {
		$server_max = (int) bp_media_format_size_units( bp_core_upload_max_size(), false, 'MB' );
	} else {
		$server_max = (int) ( wp_max_upload_size() / ( 1024 * 1024 ) );
	}

	if ( $value > $server_max ) {
		$value = $server_max;
	}

	return max( 1, $value );
}

/**
 * Sanitize upload limit (per batch) fields.
 *
 * Ensures the value is a positive integer with a reasonable maximum.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized upload limit.
 */
function bb_media_sanitize_upload_limit( $value ) {
	$value = absint( $value );

	return max( 1, min( $value, 100 ) );
}

/**
 * Sanitize file extensions array (video/document).
 *
 * Handles two input formats from the React admin UI:
 *
 * 1. Toggle-only update: { bb_vid_0: 1, bb_vid_1: 0, ... }
 *    Merges is_active values into the existing stored extension data.
 *
 * 2. Full extension data: { bb_vid_0: { extension: '.mp4', ... }, ... }
 *    Full sanitization of each entry (used when adding new extensions).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized extensions array.
 */
function bb_media_sanitize_extensions( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	// Determine format: if the first value is scalar (int), it's a toggle-only update.
	$first_value = reset( $value );
	$is_toggle_only = ! is_array( $first_value );

	if ( $is_toggle_only ) {
		// Determine the correct option name from key prefix.
		$first_key   = key( $value );
		$option_name = ( 0 === strpos( $first_key, 'bb_vid' ) )
			? 'bp_video_extensions_support'
			: 'bp_document_extensions_support';

		// Merge toggle states into existing stored data.
		$existing = bp_get_option( $option_name, array() );

		foreach ( $value as $key => $is_active ) {
			$sanitized_key = sanitize_key( $key );

			if ( isset( $existing[ $sanitized_key ] ) ) {
				$existing[ $sanitized_key ]['is_active'] = absint( $is_active );
			}
		}

		return $existing;
	}

	// Full extension data format.
	$sanitized = array();

	foreach ( $value as $key => $ext ) {
		if ( ! is_array( $ext ) ) {
			continue;
		}

		$sanitized_key = sanitize_key( $key );

		$sanitized[ $sanitized_key ] = array(
			'extension'   => isset( $ext['extension'] ) ? sanitize_text_field( $ext['extension'] ) : '',
			'mime_type'   => isset( $ext['mime_type'] ) ? sanitize_mime_type( $ext['mime_type'] ) : '',
			'description' => isset( $ext['description'] ) ? sanitize_text_field( $ext['description'] ) : '',
			'is_default'  => isset( $ext['is_default'] ) ? absint( $ext['is_default'] ) : 0,
			'is_active'   => isset( $ext['is_active'] ) ? absint( $ext['is_active'] ) : 0,
			'icon'        => isset( $ext['icon'] ) ? sanitize_text_field( $ext['icon'] ) : '',
		);
	}

	return $sanitized;
}

/**
 * Sanitize GIPHY API key.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized API key.
 */
function bb_media_sanitize_gif_api_key( $value ) {
	return sanitize_text_field( wp_unslash( $value ) );
}

/**
 * Sanitize access control fields.
 *
 * Access control values are stored as an array with a 'default' key (select value)
 * and additional role/profile type keys (toggle values).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized access control data.
 */
function bb_media_sanitize_access_controls( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();

	foreach ( $value as $key => $val ) {
		$sanitized_key = sanitize_key( $key );

		if ( 'default' === $sanitized_key ) {
			// The select dropdown value (e.g., 'members', 'specific').
			$sanitized[ $sanitized_key ] = sanitize_text_field( $val );
		} else {
			// Role/profile type toggle values.
			$sanitized[ $sanitized_key ] = absint( $val );
		}
	}

	return $sanitized;
}
