<?php
/**
 * The template for member courses for tutorlms.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/tutor/courses.php.
 *
 * @since 2.4.40
 *
 * @package BuddyBoss\TutorLMS
 *
 * @version 1.0.0
 */

$current_course_subtab = 'enrolled-courses';
if ( class_exists( 'BB_TutorLMS_Profile' ) ) {
	$bb_tutorlms_profile   = BB_TutorLMS_Profile::get_instance();
	$current_course_subtab = $bb_tutorlms_profile->profile_course_subtab;
}

if ( 'instructor-courses' === $current_course_subtab ) {
	$courses_list = bb_tutorlms_get_instructor_courses();
} else {
	$courses_list = bb_tutorlms_get_enrolled_courses();
}

if ( ! empty( $courses_list->posts ) ) {
	$courses_list = $courses_list->posts;
}

$course_ids = array();
if ( ! empty( $courses_list ) && is_array( $courses_list ) ) {
	foreach ( $courses_list as $course ) {
		if ( is_numeric( $course ) ) {

			// Check if the item is a numeric ID (integer or string representation of an integer).
			$course_ids[] = intval( $course );
		} elseif ( is_object( $course ) ) {

			// If the item is an object, you can access its ID property.
			$course_ids[] = $course->ID;
		}
	}
}

$course_ids = array_unique( $course_ids );

if ( ! function_exists( 'bb_enable_content_counts' ) || bb_enable_content_counts() ) {
	$count = count( $course_ids );
	?>
	<div class="bb-item-count">
		<?php
		/* translators: %d is the courses count */
		printf(
			wp_kses( _n( '<span class="bb-count">%d</span> Course', '<span class="bb-count">%d</span> Courses', $count, 'buddyboss-pro' ), array( 'span' => array( 'class' => true ) ) ),
			$count
		);
		?>
	</div>
	<?php
	unset( $count );
}

if ( ! empty( $course_ids ) ) {
	$course_ids_string = implode( ',', $course_ids );
	echo tutor_lms()->shortcode->tutor_course( array( 'id' => $course_ids_string, 'show_pagination' => 'on' ) );
} else {
	if ( 'instructor-courses' === $current_course_subtab ) {
		bp_nouveau_user_feedback( 'tutorlms-created-courses-loop-none' );
	} else {
		bp_nouveau_user_feedback( 'tutorlms-courses-loop-none' );
	}
}

