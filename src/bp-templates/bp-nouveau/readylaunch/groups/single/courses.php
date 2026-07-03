<?php
/**
 * BP Nouveau Group's edit courses template.
 * BuddyBoss - Groups Courses
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/courses.php.
 *
 * @since   2.4.40
 *
 * @package BuddyBoss\TutorLMS
 *
 * @version 1.0.0
 */

$group_id = bp_get_group_id();
if ( ! $group_id ) {
	return;
}

$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
	array(
		'group_id' => $group_id,
		'fields'   => 'course_id',
		'per_page' => false,
	),
);

if ( ! empty( $bb_tutorlms_groups['courses'] ) && tutor_utils()->count( $bb_tutorlms_groups['courses'] ) ) {
	$course_ids_string = implode( ',', $bb_tutorlms_groups['courses'] );
	echo tutor_lms()->shortcode->tutor_course( array( 'id' => $course_ids_string, 'show_pagination' => 'on', 'post_status' => array('publish', 'private') ) );
}
