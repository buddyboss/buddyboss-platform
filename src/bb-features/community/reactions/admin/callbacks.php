<?php
/**
 * BuddyBoss Admin Settings - Reactions Callbacks.
 *
 * Sanitize and render callback functions for Reactions feature settings.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize reaction content types.
 *
 * Handles the toggle_list field type for bb_all_reactions option.
 * Accepts associative array like: { activity: 1, activity_comment: 1, blogs: 0, private_message: 0 }
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array
 */
function bb_reactions_sanitize_content_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	/**
	 * Filters the allowed reaction content type keys.
	 *
	 * Allows Pro or third-party plugins to add support for additional
	 * content types (e.g. 'blogs', 'private_message').
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $allowed_keys Allowed content type slugs.
	 */
	$allowed_keys = apply_filters( 'bb_reactions_allowed_content_types', array( 'activity', 'activity_comment' ) );
	$sanitized    = array();

	foreach ( $allowed_keys as $key ) {
		$sanitized[ $key ] = isset( $value[ $key ] ) ? (bool) $value[ $key ] : false;
	}

	return $sanitized;
}

/**
 * Sanitize reactions button settings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array
 */
function bb_reactions_sanitize_button_settings( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();

	if ( isset( $value['icon'] ) ) {
		$sanitized['icon'] = sanitize_text_field( $value['icon'] );
	}

	if ( isset( $value['text'] ) ) {
		$text              = trim( stripslashes( sanitize_text_field( $value['text'] ) ) );
		$sanitized['text'] = mb_strlen( $text ) > 12 ? mb_substr( $text, 0, 12 ) : $text;
	}

	return $sanitized;
}

/**
 * Get all of the reactions settings fields.
 *
 * @since BuddyBoss 2.5.20
 * @since BuddyBoss [BBVERSION] Moved to Settings 2.0 location.
 *
 * @return array
 */
function bb_reactions_get_settings_fields() {

	$fields = array();

	$fields['bp_reaction_settings_section'] = array(
		'bb_all_reactions'     => array(
			'title' => esc_html__( 'Enable Reactions', 'buddyboss' ),
		),

		'bb_reaction_mode'     => array(
			'title'             => esc_html__( 'Reactions Mode', 'buddyboss' ),
			'sanitize_callback' => 'sanitize_text_field',
		),

		'bb_reaction_emotions' => array(),

		'bb_reactions_button'  => array(
			'title' => esc_html__( 'Reactions Button', 'buddyboss' ),
		),
	);

	return (array) apply_filters( 'bb_reactions_get_settings_fields', $fields );
}
