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
add_filter( 'sfwd_cpt_options', 'bb_ld_group_archive_slug_change', 999, 2 );
add_filter( 'learndash_settings_fields', 'bb_ld_group_archive_backend_slug_print', 9999, 2 );

/* Actions *******************************************************************/
add_action( 'add_meta_boxes', 'bp_activity_add_meta_boxes', 50 );

add_action( 'admin_bar_menu', 'bb_group_wp_admin_bar_updates_menu', 99 );

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

	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

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

/**
 * Learndash Plugin updates Group Page admin bar Edit link to dashboard home page instead of the platform groups page.
 * Filter fix the issue and making sure platform group page is being edited.
 *
 * @since BuddyBoss 1.4.7
 */
function bb_group_wp_admin_bar_updates_menu() {
	global $wp_admin_bar;

	$page_ids = bp_core_get_directory_page_ids();
	if ( bp_is_groups_directory() && is_array( $page_ids ) && isset( $page_ids['groups'] ) && !empty( $page_ids['groups'] ) ) {

		//Get a reference to the edit node to modify.
		$new_content_node = $wp_admin_bar->get_node('edit');

		if ( isset( $new_content_node ) && ! empty( $new_content_node->href ) ) {
			//Change href
			$new_content_node->href = get_edit_post_link( $page_ids['groups'] );
		}

		//Update Node.
		$wp_admin_bar->add_node($new_content_node);

	}
}

/**
 * Filter to fix conflict between Learndash Plugin groups archive page and Platform Groups page.
 *
 * @since BuddyBoss 1.4.7
 * 
 * @param array  $post_options An array of post options.
 * @param string $post_type    Post type slug.
 * 
 * @return array $post_options
 */
function bb_ld_group_archive_slug_change( $post_options, $post_type ) {
	$page_ids = bp_core_get_directory_page_ids();

	if ( bp_is_active( 'groups') && is_array( $page_ids ) && isset( $page_ids['groups'] ) && !empty( $page_ids['groups'] ) && learndash_get_post_type_slug( 'group' ) === $post_type ) {
		$post_options['rewrite']['slug'] = 'ld-groups';
	}

	return $post_options;
}

/**
 * Filter to fix conflict between Learndash Plugin groups archive page and Platform Groups page.
 * Show the proper archive page link on LD domain.com/wp-admin/admin.php?page=groups-options page.
 *
 * @since BuddyBoss 1.4.7
 * 
 * @param array  $setting_option_fields Associative array of Setting field details like name,type,label,value.
 * @param string $settings_section_key Used within the Settings API to uniquely identify this section.
 * 
 * @return array $setting_option_fields
 */
function bb_ld_group_archive_backend_slug_print( $setting_option_fields, $settings_section_key) {

	if ( is_admin() && isset( $_REQUEST ) && isset( $_REQUEST['page'] ) && 'groups-options' === $_REQUEST['page'] && 'cpt_options' === $settings_section_key && is_array( $setting_option_fields ) && isset( $setting_option_fields['has_archive']['options']['yes'] ) ) {
		$setting_option_fields['has_archive']['options']['yes'] = sprintf(
		// translators: placeholder: URL for CPT Archive.
			esc_html_x( 'Archive URL: %s', 'placeholder: URL for CPT Archive', 'buddyboss' ),
			'<code><a target="blank" href="' . home_url( 'ld-groups' ) . '">' . home_url( 'ld-groups' ) . '</a></code>'
		);
	}

	return $setting_option_fields;

}
