<?php
/**
 * BuddyBoss Admin Settings - Activity Callbacks.
 *
 * Sanitize callback functions for Activity feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get allowed edit time values (cached per request).
 *
 * Returns an array of allowed integer values for activity/comment edit time,
 * including -1 (Forever). Falls back to hardcoded defaults when
 * bp_activity_edit_times() is unavailable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Allowed integer values.
 */
function bb_activity_get_allowed_edit_times() {
	static $allowed = null;

	if ( null !== $allowed ) {
		return $allowed;
	}

	$allowed = array( -1 );
	if ( function_exists( 'bp_activity_edit_times' ) ) {
		foreach ( bp_activity_edit_times() as $time ) {
			$allowed[] = intval( $time['value'] );
		}
	}

	// Fallback: ensure common defaults are always valid when BP function is unavailable.
	if ( 1 === count( $allowed ) ) {
		$allowed = array_merge( $allowed, array( 120, 300, 600, 1800, 3600, 43200, 86400 ) );
	}

	return $allowed;
}

/**
 * No-op sanitize callback for topic_list fields.
 *
 * Topics are managed via dedicated AJAX endpoints (bb_add_topic,
 * bb_delete_topic, bb_migrate_topic, bb_update_topics_order) and
 * should not be overwritten by the auto-save pipeline. This callback
 * returns the existing stored value so it is never clobbered.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value (ignored).
 *
 * @return mixed The existing stored value.
 */
function bb_sanitize_topic_list_noop( $value ) {
	return bp_get_option( 'bb_activity_topics', array() );
}

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

	if ( ! in_array( $value, bb_activity_get_allowed_edit_times(), true ) ) {
		return 600; // Fallback: invalid value, return 10 minutes.
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

	if ( ! in_array( $value, bb_activity_get_allowed_edit_times(), true ) ) {
		return 600; // Fallback: invalid value, return 10 minutes.
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
	$value = absint( $value );

	/**
	 * Filters the allowed values for the comment loading setting.
	 * Same filter as legacy bp-admin-setting-activity.php.
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @param array $allowed Allowed integer values.
	 */
	$allowed = apply_filters( 'bb_activity_comment_loading_options', array( 5, 10, 15, 20, 25, 30 ) );

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
 * Sync blogs component activation after activity CPT feed settings are saved.
 *
 * Uses the shared bb_sync_blogs_component_state() helper, reading saved option
 * values instead of $_POST (Settings 2.0 saves options before this hook fires).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_activity_sync_blogs_component_after_save( $feature_id, $settings, $saved ) {
	if ( 'activity' !== $feature_id ) {
		return;
	}

	bb_sync_blogs_component_state(
		function ( $cpt ) {
			$option_name = bb_post_type_feed_option_name( $cpt );
			return (bool) bp_get_option( $option_name, false );
		}
	);

	// If a post type feed is disabled, also disable its comments option.
	// Mirrors legacy behavior from bb_after_update_activity_settings().
	foreach ( bb_feed_post_types() as $post_type ) {
		$pt_opt_name  = bb_post_type_feed_option_name( $post_type );
		$ptc_opt_name = bb_post_type_feed_comment_option_name( $post_type );

		if ( empty( bp_get_option( $pt_opt_name, '' ) ) ) {
			bp_update_option( $ptc_opt_name, 0 );
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_activity_sync_blogs_component_after_save', 10, 3 );
