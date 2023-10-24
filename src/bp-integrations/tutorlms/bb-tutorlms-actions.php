<?php
/**
 * TutorLMS integration actions
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'save_tutor_course', 'bb_save_tutor_course', 10, 2 );

/**
 * Function to add activity record once course published from front side.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int   $post_ID   Post ID.
 * @param array $post_data Post Data.
 *
 * @return void
 */
function bb_save_tutor_course( $post_ID, $post_data ) {
	if ( ! tutor_utils()->is_instructor( bp_loggedin_user_id() ) || ! current_user_can( 'administrator' ) ) {
		return;
	}

	$can_publish_course = (bool) tutor_utils()->get_option( 'instructor_can_publish_course' );
	if ( $can_publish_course ) {
		/**
		 * @todo - We are not getting post_type and other data's in $post_data variable.
		 * So we need to fetch post data from post_id.
		 * We can remove get_post once we get all required data from $post_data as functions args.
		 */
		$post_data = get_post( $post_ID );
		bp_activity_post_type_publish( $post_ID, $post_data );
	}
}
