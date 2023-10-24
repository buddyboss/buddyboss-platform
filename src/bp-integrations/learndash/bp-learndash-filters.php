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
add_action( 'bp_core_set_uri_globals', 'bb_support_learndash_course_permalink', 10, 2 );

add_action( 'add_meta_boxes', 'bp_activity_add_meta_boxes', 50 );

add_action( 'admin_bar_menu', 'bb_group_wp_admin_bar_updates_menu', 99 );

// Support other languages slug for LD.
add_filter( 'bp_get_requested_url', 'bb_support_learndash_course_other_language_permalink', 10, 1 );
add_filter( 'bp_uri', 'bb_support_learndash_course_other_language_permalink', 10, 1 );

// Support for learndash nested urls.
add_filter( 'learndash_permalinks_nested_urls', 'bb_support_learndash_permalinks_nested_urls', 9999, 3 );

/** Functions *****************************************************************/

/**
 * Do not redirect to user on register page if user doing registration on LD Popup.
 *
 * @param bool $bool
 *
 * @since BuddyBoss 1.2.3
 */
function bp_ld_popup_register_redirect( $bool ) {

	if ( isset( $_POST ) && isset( $_POST['learndash-registration-form'] ) ) {
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

	if ( ! bp_is_active( 'activity' ) || empty( $post ) ) {
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

	if (
		bp_is_active( 'groups' ) &&
		is_array( $page_ids ) &&
		isset( $page_ids['groups'] ) &&
		! empty( $page_ids['groups'] ) &&
		function_exists( 'learndash_get_post_type_slug' ) &&
		learndash_get_post_type_slug( 'group' ) === $post_type &&
		isset( $post_options['rewrite']['slug'] ) &&
		learndash_get_post_type_slug( 'group' ) === $post_options['rewrite']['slug']
	) {
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

/**
 * Update the current component and action while nested URL setup from the learndash permalink.
 * For member courses.
 *
 * @since BuddyBoss 1.5.9
 *
 * @param object $bp     BuddyPress object.
 * @param array  $bp_uri Array of URI.
 */
function bb_support_learndash_course_permalink( $bp, $bp_uri ) {
	if ( ! empty( $bp_uri ) && implode( '/', $bp_uri ) === bb_learndash_profile_courses_slug() ) {
		$bp->current_component = bb_learndash_profile_courses_slug();
		$bp->current_action    = '';
	}
}

/**
 * Update the URL setup from the learndash permalink.
 *
 * @since BuddyBoss 2.1.0
 *
 * @param string $url URL to be redirected.
 *
 * @return string URL of the current page.
 */
function bb_support_learndash_course_other_language_permalink( $url ) {
	// Original URL.
	$un_trailing_slash_url           = rtrim( $url, '/' );
	$exploded_un_trailing_slash_url  = ! empty( $un_trailing_slash_url ) ? explode( '/', $un_trailing_slash_url ) : array();
	$un_trailing_slash_url_last_part = ! empty( $exploded_un_trailing_slash_url ) ? array( end( $exploded_un_trailing_slash_url ) ) : array();

	// Decoded URL.
	$rawurldecode_url           = rawurldecode( $un_trailing_slash_url );
	$exploded_rawurldecode_url  = ! empty( $rawurldecode_url ) ? explode( '/', $rawurldecode_url ) : array();
	$rawurldecode_url_last_part = ! empty( $exploded_rawurldecode_url ) ? array( end( $exploded_rawurldecode_url ) ) : array();

	if (
		class_exists( 'LearnDash_Settings_Section' ) &&
		! empty( $rawurldecode_url_last_part ) &&
		! empty( $un_trailing_slash_url_last_part ) &&
		in_array( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ), $rawurldecode_url_last_part, true ) &&
		urldecode( current( $rawurldecode_url_last_part ) ) !== current( $un_trailing_slash_url_last_part )
	) {
		return rawurldecode( $url );
	}

	return $url;
}

/**
 * Forum's shortcode pagination support for the learndash permalink nested urls.
 *
 * @since BuddyBoss 2.3.0
 *
 * @param array $ld_rewrite_rules    rewrite rules.
 * @param array $ld_rewrite_patterns rewrite rules structure with placeholder.
 * @param array $ld_rewrite_values   rewrite rules placeholders for slug and name.
 *
 * @return array $ld_rewrite_rules rewrite rules.
 */
function bb_support_learndash_permalinks_nested_urls( $ld_rewrite_rules, $ld_rewrite_patterns, $ld_rewrite_values ) {

	if (
		class_exists( 'LearnDash_Settings_Section' ) &&
		'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' )
	) {
		$ld_bb_rewrite_patterns = array(
			// Learndash Course > Quiz.
			'{{courses_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)/page(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{quizzes_cpt_name}}=$matches[2]&paged=$matches[3]',

			// Learndash Course > Lesson.
			'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/page(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&paged=$matches[3]',

			// Learndash Course > Lesson > Quiz.
			'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)/page(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{quizzes_cpt_name}}=$matches[3]&paged=$matches[4]',

			// Learndash Course > Lesson > Topic.
			'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{topics_cpt_slug}}/([^/]+)/page(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{topics_cpt_name}}=$matches[3]&paged=$matches[4]',

			// Learndash Course > Lesson > Topic > Quiz.
			'{{courses_cpt_slug}}/([^/]+)/{{lessons_cpt_slug}}/([^/]+)/{{topics_cpt_slug}}/([^/]+)/{{quizzes_cpt_slug}}/([^/]+)page(?:/([0-9]+))?/?$' => 'index.php?{{courses_cpt_name}}=$matches[1]&{{lessons_cpt_name}}=$matches[2]&{{topics_cpt_name}}=$matches[3]&{{quizzes_cpt_name}}=$matches[4]&pagde=$matches[5]',
		);

		if (
			! empty( $ld_bb_rewrite_patterns ) &&
			! empty( $ld_rewrite_values )
		) {
			foreach ( $ld_bb_rewrite_patterns as $rewrite_pattern_key => $rewrite_pattern_rule ) {
				foreach ( $ld_rewrite_values as $post_type_name => $ld_rewrite_values_sets ) {
					if ( ! empty( $ld_rewrite_values_sets ) ) {
						foreach ( $ld_rewrite_values_sets as $ld_rewrite_values_set_key => $ld_rewrite_values_set ) {
							if ( ! empty( $ld_rewrite_values_set ) ) {
								if ( ( ! isset( $ld_rewrite_values_set['placeholder'] ) ) || ( empty( $ld_rewrite_values_set['placeholder'] ) ) ) {
									continue;
								}
								if ( ( ! isset( $ld_rewrite_values_set['value'] ) ) || ( empty( $ld_rewrite_values_set['value'] ) ) ) {
									continue;
								}

								$rewrite_pattern_key  = str_replace( $ld_rewrite_values_set['placeholder'], $ld_rewrite_values_set['value'], $rewrite_pattern_key );
								$rewrite_pattern_rule = str_replace( $ld_rewrite_values_set['placeholder'], $ld_rewrite_values_set['value'], $rewrite_pattern_rule );
							}
						}
					}
				}
				$ld_rewrite_rules[ $rewrite_pattern_key ] = $rewrite_pattern_rule;
			}
		}
	}

	return $ld_rewrite_rules;
}
