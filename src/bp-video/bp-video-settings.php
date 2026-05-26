<?php
/**
 * Video Settings
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if profile video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is profile video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_profile_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if profile video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_profile_video_support_enabled', (bool) get_option( 'bp_video_profile_video_support', $default ) );
}

/**
 * Checks if group video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is group video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_group_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if group video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_group_video_support_enabled', (bool) get_option( 'bp_video_group_video_support', $default ) );
}

/**
 * Checks if messages video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is messages video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_messages_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if message video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_messages_video_support_enabled', (bool) get_option( 'bp_video_messages_video_support', $default ) );
}

/**
 * Checks if forums video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is forums video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_forums_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if forums video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_forums_video_support_enabled', (bool) get_option( 'bp_video_forums_video_support', $default ) );
}

/**
 * Allowed upload file size for the video.
 *
 * @return int Allowed upload file size for the video.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_allowed_upload_video_size() {
	$max_size = bp_core_upload_max_size();
	$default  = bp_video_format_size_units( $max_size, false, 'MB' );
	/**
	 * Filters for upload file size for the video.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (int) apply_filters( 'bp_video_allowed_upload_video_size', (int) get_option( 'bp_video_allowed_size', $default ) );
}

/**
 * Allowed per batch for the video.
 *
 * @return int Allowed upload per batch for the video.
 * @since BuddyBoss 1.7.0
 */
function bp_video_allowed_upload_video_per_batch() {

	/**
	 * Filters for allowed per batch for the video.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$default = apply_filters( 'bp_video_upload_chunk_limit', 10 );

	/**
	 * Filters for allowed per batch for the video.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (int) apply_filters( 'bp_video_allowed_upload_video_per_batch', (int) get_option( 'bp_video_allowed_per_batch', $default ) );
}
