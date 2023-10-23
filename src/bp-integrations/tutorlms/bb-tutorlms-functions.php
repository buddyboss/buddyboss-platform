<?php
/**
 * TutorLMS integration helpers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss\TutorLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns TutorLMS Integration url.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $path Path to tutorlms integration.
 */
function bb_tutorlms_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->integration_url ) . 'tutorlms/' . trim( $path, '/\\' );
}

/**
 * Returns TutorLMS Integration path.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $path Path to tutorlms integration.
 */
function bb_tutorlms_integration_path( $path = '' ) {
	return trailingslashit( buddypress()->integration_dir ) . 'tutorlms/' . trim( $path, '/\\' );
}

/**
 * Get TutorLMS settings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $key     Optional. Get setting by key.
 * @param string $default Optional. Default value if value or setting not available.
 *
 * @return array|string
 */
function bb_get_tutorlms_settings( $key = '', $default = '' ) {
	$settings = bp_get_option( 'bb-tutorlms', array() );

	if ( ! empty( $key ) ) {
		$settings = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	} elseif ( empty( $settings ) ) {
		$settings = array();
	}

	return apply_filters( 'bb_get_tutorlms_settings', $settings, $key, $default );
}

/**
 * Checks if TutorLMS enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS enabled by default.
 *
 * @return bool Is TutorLMS enabled or not.
 */
function bb_tutorlms_enable( $default = 0 ) {

	/**
	 * Filters TutorLMS enabled settings.
	 *
	 * @param integer $default TutorLMS enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_enable', bb_get_tutorlms_settings( 'bb-tutorlms-enable', $default ) );
}

/**
 * Checks if TutorLMS group course enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS group course enabled by default.
 *
 * @return bool Is TutorLMS group course enabled or not.
 */
function bb_tutorlms_group_course_tab( $default = 0 ) {

	/**
	 * Filters TutorLMS group course enabled settings.
	 *
	 * @param integer $default TutorLMS group course enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_group_course_tab', bb_get_tutorlms_settings( 'bb-tutorlms-group-course-tab', $default ) );
}

/**
 * Checks if TutorLMS course tab visibility enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS course tab visibility  enabled by default.
 *
 * @return bool Is TutorLMS course tab visibility enabled or not.
 */
function bb_tutorlms_course_tab_visibility( $default = 0 ) {

	/**
	 * Filters TutorLMS course tab visibility  enabled settings.
	 *
	 * @param integer $default TutorLMS course tab visibility enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_course_tab_visibility', bb_get_tutorlms_settings( 'bb-tutorlms-course-tab-visibility', $default ) );
}

/**
 * Checks if TutorLMS course visibility enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS course visibility  enabled by default.
 *
 * @return bool Is TutorLMS course visibility enabled or not.
 */
function bb_tutorlms_course_visibility( $default = 0 ) {

	/**
	 * Filters TutorLMS course visibility  enabled settings.
	 *
	 * @param integer $default TutorLMS course visibility enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_course_visibility', bb_get_tutorlms_settings( 'bb-tutorlms-course-visibility', $default ) );
}

/**
 * Checks if TutorLMS user enrolled course enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user enrolled course enabled by default.
 *
 * @return bool Is TutorLMS user enrolled course enabled or not.
 */
function bb_tutorlms_user_enrolled_course( $default = 0 ) {

	/**
	 * Filters TutorLMS user enrolled course enabled settings.
	 *
	 * @param integer $default TutorLMS user enrolled course enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_enrolled_course', bb_get_tutorlms_settings( 'bb-tutorlms-user-enrolled-course', $default ) );
}

/**
 * Checks if TutorLMS user started course enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user started course enabled by default.
 *
 * @return bool Is TutorLMS user started course enabled or not.
 */
function bb_tutorlms_user_started_course( $default = 0 ) {

	/**
	 * Filters TutorLMS user started course enabled settings.
	 *
	 * @param integer $default TutorLMS user started course enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_started_course', bb_get_tutorlms_settings( 'bb-tutorlms-user-started-course', $default ) );
}

/**
 * Checks if TutorLMS user completes course enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user completes course enabled by default.
 *
 * @return bool Is TutorLMS user completes course enabled or not.
 */
function bb_tutorlms_user_completes_course( $default = 0 ) {

	/**
	 * Filters TutorLMS user completes course enabled settings.
	 *
	 * @param integer $default TutorLMS user completes course enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_completes_course', bb_get_tutorlms_settings( 'bb-tutorlms-user-completes-course', $default ) );
}

/**
 * Checks if TutorLMS user creates lesson enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user creates lesson enabled by default.
 *
 * @return bool Is TutorLMS user creates lesson enabled or not.
 */
function bb_tutorlms_user_creates_lesson( $default = 0 ) {

	/**
	 * Filters TutorLMS user creates lesson enabled settings.
	 *
	 * @param integer $default TutorLMS user creates lesson enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_creates_lesson', bb_get_tutorlms_settings( 'bb-tutorlms-user-creates-lesson', $default ) );
}

/**
 * Checks if TutorLMS user updates lesson enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user updates lesson enabled by default.
 *
 * @return bool Is TutorLMS user updates lesson enabled or not.
 */
function bb_tutorlms_user_updates_lesson( $default = 0 ) {

	/**
	 * Filters TutorLMS user updates lesson enabled settings.
	 *
	 * @param integer $default TutorLMS user updates lesson enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_updates_lesson', bb_get_tutorlms_settings( 'bb-tutorlms-user-updates-lesson', $default ) );
}

/**
 * Checks if TutorLMS user started quiz enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user started quiz enabled by default.
 *
 * @return bool Is TutorLMS user started quiz enabled or not.
 */
function bb_tutorlms_user_started_quiz( $default = 0 ) {

	/**
	 * Filters TutorLMS user started quiz enabled settings.
	 *
	 * @param integer $default TutorLMS user started quiz enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_started_quiz', bb_get_tutorlms_settings( 'bb-tutorlms-user-started-quiz', $default ) );
}

/**
 * Checks if TutorLMS user finished quiz enable.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param integer $default TutorLMS user finished quiz enabled by default.
 *
 * @return bool Is TutorLMS user finished quiz enabled or not.
 */
function bb_tutorlms_user_finished_quiz( $default = 0 ) {

	/**
	 * Filters TutorLMS user finished quiz enabled settings.
	 *
	 * @param integer $default TutorLMS user finished quiz enabled by default.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_user_finished_quiz', bb_get_tutorlms_settings( 'bb-tutorlms-user-finished-quiz', $default ) );
}

/**
 * Function to return all TutorLMS post types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return mixed|null
 */
function bb_tutorlms_get_post_types() {
	$tutorlms_post_types = array(
		tutor()->course_post_type,
		tutor()->lesson_post_type,
		tutor()->topics_post_type,
		tutor()->assignment_post_type,
		tutor()->enrollment_post_type,
		tutor()->quiz_post_type,
	);

	return apply_filters( 'bb_tutorlms_get_post_types', $tutorlms_post_types );
}

/**
 * Checks if post type feed is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $post_type Post Type.
 * @param bool   $default Optional. Fallback value if not found in the database.
 *                        Default: false.
 *
 * @return bool Is post type feed enabled or not
 */
function bb_tutorlms_post_type_feed_enable( $post_type, $default = false ) {
	$option_name = bb_post_type_feed_option_name( $post_type );

	/**
	 * Filters whether post type feed enabled or not.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $value Whether post type feed enabled or not.
	 */
	return (bool) apply_filters( 'bb_tutorlms_post_type_feed_enable', bb_get_tutorlms_settings( $option_name, $default ) );
}

/**
 * Describe the activity comment is enable or not for custom post type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $post_type custom post type.
 * @param bool $default   Optional. Fallback value if not found in the database.
 *                        Default: false.
 * @return bool True if activity comments are enable for blog and forum
 *              items, otherwise false.
 */
function bb_tutorlms_post_type_feed_comment_enable( $post_type, $default = false ) {
	$option_name = bb_post_type_feed_comment_option_name( $post_type );

	/**
	 * Filters whether or not custom post type feed comments are enable.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $value Whether or not custom post type activity feed comments are enable.
	 */
	return (bool) apply_filters( 'bb_tutorlms_post_type_feed_comment_enable', bb_get_tutorlms_settings( $option_name, $default ) );
}
