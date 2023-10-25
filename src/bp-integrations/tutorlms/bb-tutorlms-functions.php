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
 * @param string $keys    Optional. Get setting by key.
 * @param string $default Optional. Default value if value or setting not available.
 *
 * @return array|string
 */
function bb_get_tutorlms_settings( $keys = '', $default = '' ) {
	$settings = bp_get_option( 'bb-tutorlms', array() );

	if ( ! empty( $keys ) ) {
		if ( is_string( $keys ) ) {
			$keys = explode( '.', $keys );
		}

		foreach ( $keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings = $settings[ $key ];
			} else {
				return $default;
			}
		}
	} elseif ( empty( $settings ) ) {
		$settings = array();
	}

	return apply_filters( 'bb_get_tutorlms_settings', $settings, $keys, $default );
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
 * Function to get enabled TutorLMS courses activities.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $key TutorLMS course activity slug.
 *
 * @return array Is any TutorLMS courses activities enabled.
 */
function bb_get_enabled_tutorlms_course_activities( $key ) {

	$option_name = ! empty( $key ) ? 'bb-tutorlms-course-activity.' . $key : '';

	/**
	 * Filters to get enabled TutorLMS courses activities.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	return apply_filters( 'bb_tutorlms_course_activities', bb_get_tutorlms_settings( $option_name ) );
}

/**
 * Function to return all TutorLMS post types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return mixed|null
 */
function bb_tutorlms_get_post_types() {
	if ( ! function_exists( 'tutor' ) ) {
		return;
	}

	$tutorlms_post_types = array(
		tutor()->course_post_type,
		tutor()->lesson_post_type,
		tutor()->quiz_post_type,
		tutor()->assignment_post_type,
	);

	return apply_filters( 'bb_tutorlms_get_post_types', $tutorlms_post_types );
}

/**
 * TutorLMS course activities.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array
 */
function bb_tutrolms_course_activities( $keys = array() ) {
	$activities = array(
		'bb-tutorlms-user-enrolled-course'  => __( 'User enrolled in a course', 'buddyboss' ),
		'bb-tutorlms-user-started-course'   => __( 'User started a course', 'buddyboss' ),
		'bb-tutorlms-user-completes-course' => __( 'User completes a course', 'buddyboss' ),
		'bb-tutorlms-user-creates-lesson'   => __( 'User creates a lesson', 'buddyboss' ),
		'bb-tutorlms-user-updates-lesson'   => __( 'User updates a lesson', 'buddyboss' ),
		'bb-tutorlms-user-started-quiz'     => __( 'User started a quiz', 'buddyboss' ),
		'bb-tutorlms-user-finished-quiz'    => __( 'User finished a quiz', 'buddyboss' ),
	);

	$result = ! empty( $keys ) ? array_intersect_key( $activities, $keys ) : $activities;

	return $result;
}

/**
 * Check TutorLMS course is setup or not for group main tab.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool Returns true if TutorLMS is setup.
 */
function bb_tutorlms_is_group_setup() {
	if (
		! bp_is_active( 'groups' ) ||
		! bb_tutorlms_enable() ||
		! bb_tutorlms_group_course_tab()
	) {
		return false;
	}

	return true;
}
