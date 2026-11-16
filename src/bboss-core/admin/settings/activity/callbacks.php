<?php
/**
 * BuddyBoss Admin Settings - Activity Callbacks.
 *
 * Sanitize callback functions for Activity feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * No-op sanitize callback for topic_list fields.
 *
 * Topics are managed via dedicated AJAX endpoints (bb_add_topic,
 * bb_delete_topic, bb_migrate_topic, bb_update_topics_order) and
 * should not be overwritten by the auto-save pipeline. This callback
 * returns the existing stored value so it is never clobbered.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The submitted value (ignored).
 *
 * @return mixed The existing stored value.
 */
function bboss_sanitize_topic_list_noop( $value ) {
	return bboss_get_option( 'bb_activity_topics', array() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_sanitize_topic_list_noop' ) && ! function_exists( 'bb_sanitize_topic_list_noop' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_sanitize_topic_list_noop()}.
	 *
	 * @deprecated Use bboss_sanitize_topic_list_noop() instead.
	 */
	function bb_sanitize_topic_list_noop( $value ) {
		return bboss_sanitize_topic_list_noop( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize activity edit time setting.
 *
 * Handles the toggle + select combo for activity edit and comment edit fields.
 *
 * @since BuddyBoss 3.0.0
 * @since BuddyBoss 3.5.1 Delegates to bboss_activity_normalize_edit_time().
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value.
 */
function bboss_activity_sanitize_edit_time( $value ) {
	// Shared with the read path so both sides resolve an out-of-range duration identically.
	return bboss_activity_normalize_edit_time( $value );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sanitize_edit_time' ) && ! function_exists( 'bb_activity_sanitize_edit_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sanitize_edit_time()}.
	 *
	 * @deprecated Use bboss_activity_sanitize_edit_time() instead.
	 */
	function bb_activity_sanitize_edit_time( $value ) {
		return bboss_activity_sanitize_edit_time( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize activity comment edit time setting.
 *
 * Delegates to bboss_activity_sanitize_edit_time() since the validation
 * rules are identical for activity posts and comments.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value.
 */
function bboss_activity_sanitize_comment_edit_time( $value ) {
	return bboss_activity_sanitize_edit_time( $value );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sanitize_comment_edit_time' ) && ! function_exists( 'bb_activity_sanitize_comment_edit_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sanitize_comment_edit_time()}.
	 *
	 * @deprecated Use bboss_activity_sanitize_comment_edit_time() instead.
	 */
	function bb_activity_sanitize_comment_edit_time( $value ) {
		return bboss_activity_sanitize_comment_edit_time( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize sharing platforms checkbox_list.
 *
 * Expects an associative array where keys are platform slugs and values are 0/1.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of platform_slug => 0|1.
 */
function bboss_sanitize_sharing_platforms( $value ) {
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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_sanitize_sharing_platforms' ) && ! function_exists( 'bb_sanitize_sharing_platforms' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_sanitize_sharing_platforms()}.
	 *
	 * @deprecated Use bboss_sanitize_sharing_platforms() instead.
	 */
	function bb_sanitize_sharing_platforms( $value ) {
		return bboss_sanitize_sharing_platforms( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize comment visibility setting.
 *
 * Accepts values 0-5 for maximum comments per post.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value (0-5).
 */
function bboss_activity_sanitize_comment_visibility( $value ) {
	$value = absint( $value );

	if ( $value > 5 ) {
		return 2;
	}

	return $value;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sanitize_comment_visibility' ) && ! function_exists( 'bb_activity_sanitize_comment_visibility' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sanitize_comment_visibility()}.
	 *
	 * @deprecated Use bboss_activity_sanitize_comment_visibility() instead.
	 */
	function bb_activity_sanitize_comment_visibility( $value ) {
		return bboss_activity_sanitize_comment_visibility( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize comment threading depth setting.
 *
 * Accepts values 1-4 for thread depth levels.
 *
 * @since BuddyBoss 3.0.0
 * @since BuddyBoss 3.5.1 Delegates to bboss_activity_normalize_comment_threading_depth().
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value (1-4).
 */
function bboss_activity_sanitize_comment_threading_depth( $value ) {
	// Shared with the read path so both sides resolve an out-of-range depth identically.
	return bboss_activity_normalize_comment_threading_depth( $value );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sanitize_comment_threading_depth' ) && ! function_exists( 'bb_activity_sanitize_comment_threading_depth' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sanitize_comment_threading_depth()}.
	 *
	 * @deprecated Use bboss_activity_sanitize_comment_threading_depth() instead.
	 */
	function bb_activity_sanitize_comment_threading_depth( $value ) {
		return bboss_activity_sanitize_comment_threading_depth( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize comment loading setting.
 *
 * Accepts specific values for number of comments to load per request.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized integer value.
 */
function bboss_activity_sanitize_comment_loading( $value ) {
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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sanitize_comment_loading' ) && ! function_exists( 'bb_activity_sanitize_comment_loading' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sanitize_comment_loading()}.
	 *
	 * @deprecated Use bboss_activity_sanitize_comment_loading() instead.
	 */
	function bb_activity_sanitize_comment_loading( $value ) {
		return bboss_activity_sanitize_comment_loading( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sanitize platform activity types toggle list.
 *
 * Handles the toggle_list for BuddyBoss Platform activity types.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of activity_name => 0|1.
 */
function bboss_activity_sanitize_platform_activity_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sanitize_platform_activity_types' ) && ! function_exists( 'bb_activity_sanitize_platform_activity_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sanitize_platform_activity_types()}.
	 *
	 * @deprecated Use bboss_activity_sanitize_platform_activity_types() instead.
	 */
	function bb_activity_sanitize_platform_activity_types( $value ) {
		return bboss_activity_sanitize_platform_activity_types( $value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Sync blogs component activation after activity CPT feed settings are saved.
 *
 * Uses the shared bboss_sync_blogs_component_state() helper, reading saved option
 * values instead of $_POST (Settings 2.0 saves options before this hook fires).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID being saved.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bboss_activity_sync_blogs_component_after_save( $feature_id, $settings, $saved ) {
	if ( 'activity' !== $feature_id ) {
		return;
	}

	bboss_sync_blogs_component_state(
		function ( $cpt ) {
			$option_name = bboss_post_type_feed_option_name( $cpt );
			return (bool) bboss_get_option( $option_name, false );
		}
	);

	// If a post type feed is disabled, also disable its comments option.
	// Mirrors legacy behavior from bb_after_update_activity_settings().
	foreach ( bboss_feed_post_types() as $post_type ) {
		$pt_opt_name  = bboss_post_type_feed_option_name( $post_type );
		$ptc_opt_name = bboss_post_type_feed_comment_option_name( $post_type );

		if ( empty( bboss_get_option( $pt_opt_name, '' ) ) ) {
			bboss_update_option( $ptc_opt_name, 0 );
		}
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_sync_blogs_component_after_save' ) && ! function_exists( 'bb_activity_sync_blogs_component_after_save' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_sync_blogs_component_after_save()}.
	 *
	 * @deprecated Use bboss_activity_sync_blogs_component_after_save() instead.
	 */
	function bb_activity_sync_blogs_component_after_save( $feature_id, $settings, $saved ) {
		return bboss_activity_sync_blogs_component_after_save( $feature_id, $settings, $saved );
	}
}
// @bbwt-com-end compat-layer


add_action( 'bb_admin_save_feature_settings_after', 'bb_activity_sync_blogs_component_after_save', 10, 3 );

/**
 * Sanitize a checkbox_list field value.
 *
 * Expects an associative array of key => 0|1 values.
 * Keys are sanitized via sanitize_key(), values cast to absint().
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Raw value from the AJAX request.
 *
 * @return array Sanitized associative array.
 */
function bboss_sanitize_checkbox_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val );
	}

	return $sanitized;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_sanitize_checkbox_list' ) && ! function_exists( 'bb_sanitize_checkbox_list' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_sanitize_checkbox_list()}.
	 *
	 * @deprecated Use bboss_sanitize_checkbox_list() instead.
	 */
	function bb_sanitize_checkbox_list( $value ) {
		return bboss_sanitize_checkbox_list( $value );
	}
}
// @bbwt-com-end compat-layer

