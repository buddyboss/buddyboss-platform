<?php
/**
 * Filters related to the BuddyBoss LearnDash integration.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 2.2.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/* Filters *******************************************************************/

// Apply WordPress defined filters.
add_filter( 'bp_activity_pre_transition_post_type_status', 'bp_activity_pre_transition_post_type_status', 10, 4 );

add_filter( 'bp_core_wpsignup_redirect', 'bp_ld_popup_register_redirect', 10 );

/* Actions *******************************************************************/
add_action( 'add_meta_boxes', 'bp_activity_add_meta_boxes', 50 );

/** Functions *****************************************************************/

/**
 * Do not redirect to user on register page if user doing registration on LD Popup.
 *
 * @param bool $bool
 *
 * @since BuddyBoss 1.2.3
 */
function bp_ld_popup_register_redirect( $bool ) {

	if (
		isset( $_POST )
		&& isset( $_POST['learndash-registration-form'] )
		&& 'true' === $_POST['learndash-registration-form']
	) {
		return false;
	}

	return $bool;
}

/**
 * Stop to add featured course's Lessons, Quizzes and Topics acvitity
 *
 * @since BuddyBoss 2.2.3
 *
 * @param  bool   $bool
 * @param  string $new_status
 * @param  string $old_status
 * @param  object $post
 *
 * @return bool $bool
 */
function bp_activity_pre_transition_post_type_status( $bool, $new_status, $old_status, $post ) {

	if (
		wp_doing_ajax()
		&& isset( $_REQUEST['action'] )
		&& (
			'learndash_builder_selector_step_new' == $_REQUEST['action']
			|| 'learndash_builder_selector_step_title' == $_REQUEST['action']
		)
	) {
		if (
			!empty( $post )
			&& (
				'sfwd-lessons' == $post->post_type
				|| 'sfwd-quiz' == $post->post_type
			)
			&& (
				empty( get_post_meta( $post->ID, 'course_id', true ) )
				|| (
					!empty( get_post_meta( $post->ID, 'course_id', true ) )
					&& 'future' === get_post_status( get_post_meta( $post->ID, 'course_id', true ) )
				)
			)
		) {
			return false;
		}

		if (
			!empty( $post )
			&& (
				'sfwd-topic' == $post->post_type
			)
			&& (
				empty( get_post_meta( $post->ID, 'course_id', true ) )
				|| (
					!empty( get_post_meta( $post->ID, 'course_id', true ) )
					&& 'future' === get_post_status( get_post_meta( $post->ID, 'course_id', true ) )
				)
			)
			&& (
				empty( get_post_meta( $post->ID, 'lesson_id', true ) )
				|| (
					!empty( get_post_meta( $post->ID, 'lesson_id', true ) )
					&& 'future' === get_post_status( get_post_meta( $post->ID, 'lesson_id', true ) )
				)
			)
		) {
			return false;
		}

	} else {

		if (
			!empty( $post )
			&& (
				'sfwd-lessons' == $post->post_type
				|| 'sfwd-quiz' == $post->post_type
			)
			&& (
				empty( get_post_meta( $post->ID, 'course_id', true ) )
				|| (
					!empty( get_post_meta( $post->ID, 'course_id', true ) )
					&& 'future' === get_post_status( get_post_meta( $post->ID, 'course_id', true ) )
				)
			)
		) {
			return false;
		}

		if (
			!empty( $post )
			&& (
				'sfwd-topic' == $post->post_type
			)
			&& (
				empty( get_post_meta( $post->ID, 'course_id', true ) )
				|| (
					!empty( get_post_meta( $post->ID, 'course_id', true ) )
					&& 'future' === get_post_status( get_post_meta( $post->ID, 'course_id', true ) )
				)
			)
			&& (
				empty( get_post_meta( $post->ID, 'lesson_id', true ) )
				|| (
					!empty( get_post_meta( $post->ID, 'lesson_id', true ) )
					&& 'future' === get_post_status( get_post_meta( $post->ID, 'lesson_id', true ) )
				)
			)
		) {
			return false;
		}
	}

	return $bool;
}


/**
 * Publish Activity for lessons, quizzes and topics with appropriate conditions.
 *
 * @since BuddyBoss 2.2.3
 */
function bp_activity_add_meta_boxes() {

	global $post;
	$post_ID = $post->ID;

	if (
		(
			'sfwd-courses' == $post->post_type
			|| 'sfwd-lessons' == $post->post_type
			|| 'sfwd-topic' == $post->post_type
			|| 'sfwd-quiz' == $post->post_type
		)
		&& !post_type_supports( $post->post_type, 'buddypress-activity' )
	) {
		return;
	}

	// Add Activity when course is published.
	if (
		'sfwd-courses' == $post->post_type
		&& $post->post_status == 'publish'
		&& post_type_supports( 'sfwd-courses', 'buddypress-activity' )
	) {

		$lesson_bb = learndash_get_course_lessons_list( $post_ID );
		$quizz = learndash_get_course_quiz_list( $post_ID );

		if ( !empty( $lesson_bb ) && post_type_supports( 'sfwd-lessons', 'buddypress-activity' ) ) {
			foreach ( $lesson_bb as $lesson ) {
				bp_activity_post_type_publish( $lesson['post']->ID, $lesson['post'] );
			}
		}

		if ( !empty( $quizz ) && post_type_supports( 'sfwd-quiz', 'buddypress-activity' ) ) {
			foreach ( $quizz as $quiz ) {
				bp_activity_post_type_publish( $quiz['post']->ID, $quiz['post'] );
			}
		}

		if ( !empty( $lesson_bb ) && post_type_supports( 'sfwd-topic', 'buddypress-activity' ) ) {
			foreach ( $lesson_bb as $lesson ) {
				$topics = learndash_get_topic_list( $lesson['post']->ID, $post_ID );
				if ( !empty( $topics ) ) {
					foreach ( $topics as $topic ) {
						bp_activity_post_type_publish( $topic->ID, $topic );
					}
				}
			}
		}

		if ( !empty( $lesson_bb ) && post_type_supports( 'sfwd-quiz', 'buddypress-activity' ) ) {
			foreach ( $lesson_bb as $lesson ) {
				$lesson_quiz = learndash_get_lesson_quiz_list( $lesson['post']->ID );
				if ( !empty( $lesson_quiz ) ) {
					foreach ( $lesson_quiz as $quiz ) {
						bp_activity_post_type_publish( $quiz['post']->ID, $quiz['post'] );
					}
				}
			}
		}
	}

	// Add Activity when lesson published correctly.
	else if (
		'sfwd-lessons' == $post->post_type
		&& $post->post_status == 'publish'
		&& post_type_supports( 'sfwd-lessons', 'buddypress-activity' )
	) {
		if (
			(
				empty( get_post_meta( $post_ID, 'course_id', true ) )
				&& learndash_is_sample( $post_ID )
			)
			|| (
				!empty( get_post_meta( $post_ID, 'course_id', true ) )
				&& 'publish' == get_post_status( get_post_meta( $post_ID, 'course_id', true ) )
			)
			|| learndash_is_sample( $post_ID )
		) {
			bp_activity_post_type_publish( $post_ID, $post );
		}
	}

	// Add Activity when topic published correctly.
	else if (
		'sfwd-topic' == $post->post_type
		&& $post->post_status == 'publish'
		&& post_type_supports( 'sfwd-topic', 'buddypress-activity' )
	) {
		if (
			   !empty( get_post_meta( $post_ID, 'course_id', true ) )
			&& !empty( get_post_meta( $post_ID, 'lesson_id', true ) )
			&& 'future' === get_post_status( get_post_meta( $post_ID, 'course_id', true ) )
			&& 'future' === get_post_status( get_post_meta( $post_ID, 'lesson_id', true ) )
		) {
			bp_activity_post_type_publish( $post_ID, $post );
		}
	}

	// Add Activity when quiz published correctly.
	else if (
		'sfwd-quiz' == $post->post_type
		&& $post->post_status == 'publish'
		&& post_type_supports( 'sfwd-quiz', 'buddypress-activity' )
	) {
		if (
			(
				!empty( get_post_meta( $post_ID, 'course_id', true ) )
				&& 'future' === get_post_status( get_post_meta( $post_ID, 'course_id', true ) )
			)
			|| (
				!empty( get_post_meta( $post_ID, 'lesson_id', true ) )
				&& 'future' === get_post_status( get_post_meta( $post_ID, 'lesson_id', true ) )
			)
		) {
			bp_activity_post_type_publish( $post_ID, $post );
		}
	}
}
