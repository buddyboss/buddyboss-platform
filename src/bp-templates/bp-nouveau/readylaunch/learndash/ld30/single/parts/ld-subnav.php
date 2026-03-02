<?php
/**
 * LearnDash Single Lesson?Topic/Quiz Navigation
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
global $post, $wpdb;

$parent_course_data = learndash_get_setting( $post, 'course' );
if ( 0 === $parent_course_data ) {
	$parent_course_data = $course_id;
	if ( 0 === $parent_course_data ) {
		$course_id = buddyboss_theme()->learndash_helper()->ld_30_get_course_id( $post->ID );
	}
	$parent_course_data = learndash_get_setting( $course_id, 'course' );
}

$parent_course       = get_post( $parent_course_data );
$parent_course_link  = $parent_course->guid;
$parent_course_title = $parent_course->post_title;
$is_enrolled         = false;
$current_user_id     = get_current_user_id();
$get_course_groups   = learndash_get_course_groups( $parent_course->ID );
$course_id           = $parent_course->ID;
$admin_enrolled      = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );

$lession_list            = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lession_list            = array_column( $lession_list, 'post' );
$user_id = get_current_user_id();

$has_access = sfwd_lms_has_access( $course_id, $user_id );
$lessons = learndash_get_course_lessons_list( $course_id );
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$quizzes = learndash_get_course_quiz_list( $course_id, $user_id );

if ( ! empty( $lessons ) ) {
	foreach ( $lessons as $lesson ) {
		$lesson_topics[ $lesson['post']->ID ] = learndash_topic_dots( $lesson['post']->ID, false, 'array', null, $course_id );
		if ( ! empty( $lesson_topics[ $lesson['post']->ID ] ) ) {
			$has_topics = true;

			$topic_pager_args                     = array(
				'course_id' => $course_id,
				'lesson_id' => $lesson['post']->ID,
			);
			$lesson_topics[ $lesson['post']->ID ] = learndash_process_lesson_topics_pager( $lesson_topics[ $lesson['post']->ID ], $topic_pager_args );
		}
	}
}

global $course_pager_results;

if ( isset( $get_course_groups ) && ! empty( $get_course_groups ) && ( function_exists( 'buddypress' ) && bp_is_active( 'groups' ) ) ) {
	foreach ( $get_course_groups as $k => $group ) {
		$bp_group_id = (int) get_post_meta( $group, '_sync_group_id', true );
		if ( ! groups_is_user_member( bp_loggedin_user_id(), $bp_group_id ) ) {
			if ( ( $key = array_search( $group, $get_course_groups ) ) !== false ) {
				unset( $get_course_groups[ $key ] );
			}
		}
	}
}

if ( sfwd_lms_has_access( $course_id, $current_user_id ) ) {
	$is_enrolled = true;
} else {
	$is_enrolled = false;
}

// if admins are enrolled.
if ( current_user_can( 'administrator' ) && 'yes' === $admin_enrolled ) {
	$is_enrolled = true;
}
?>

<div class="bb-rl-lms-sidebar-wrapper">
	<div class="bb-rl-lms-sidebar-data">
		<?php
		$course_progress = learndash_course_progress(
			array(
				'user_id'   => get_current_user_id(),
				'course_id' => $parent_course->ID,
				'array'     => true,
			)
		);

		if ( empty( $course_progress ) ) {
			$course_progress = array(
				'percentage' => 0,
				'completed'  => 0,
				'total'      => 0,
			);
		}
		?>

		<div class="bb-rl-lms-sidebar-header">
			<div class="bb-rl-lms-sidebar-course-nav">
				<a title="<?php echo esc_attr( $parent_course_title ); ?>" href="<?php echo esc_url( get_permalink( $parent_course->ID ) ); ?>" class="bb-rl-lms-sidebar-course-nav-link bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
					<span>
						<i class="bb-icons-rl-caret-left"></i>
						<?php echo sprintf( esc_html_x( 'Back to %s', 'link: Back to Course', 'buddyboss' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>
					</span>
				</a>
				<h2 class="course-entry-title"><?php echo esc_html( $parent_course_title ); ?></h2>
			</div>

			<?php if ( $is_enrolled ) { ?>
				<?php
				if ( ! empty( $course_progress ) ) {
					?>
					<div class="bb-rl-course-progress">
						<div class="bb-rl-course-progress-overview flex items-center">
							<span class="bb-rl-percentage">
								<?php
								echo wp_kses_post(
									sprintf(
									/* translators: 1: course progress percentage, 2: percentage symbol. */
										__( '<span class="bb-rl-percentage-figure">%1$s%2$s</span> Completed', 'buddyboss' ),
										(int) $course_progress['percentage'],
										'%'
									)
								);
								?>
							</span>
							<?php
							// Get completed steps.
							$completed_steps = ! empty( $course_progress['completed'] ) ? (int) $course_progress['completed'] : 0;

							// Output as "completed/total".
							if ( $course_progress['total'] > 0 ) {
								?>
								<span class="bb-rl-course-steps">
									<?php echo esc_html( $completed_steps . '/' . $course_progress['total'] ); ?>
								</span>
								<?php
							}
							?>
						</div>
						<div class="bb-rl-progress-bar">
							<div class="bb-rl-progress" style="width: <?php echo (int) $course_progress['percentage']; ?>%"></div>
						</div>
					</div>
					<?php
				}
				?>
			<?php
			}
			?>
		</div>

		<?php
		$course_progress = get_user_meta( get_current_user_id(), '_sfwd-course_progress', true );
		?>

		<div class="bb-rl-lms-sidebar-body">
			<div class="bb-rl-lms-nav-list">
				<div class="ld-item-list ld-lesson-list bb-rl-ld-lesson-list">

					<?php
					/**
					 * Fires before the course content listing
					 *
					 * @since BuddyBoss 2.9.00
					 *
					 * @param int $course_id Course ID.
					 * @param int $user_id   User ID.
					 */
					do_action( 'learndash-course-content-list-before', $course_id, $user_id );

					/**
					 * Content listing
					 *
					 * @since 3.0.0
					 *
					 * ('listing.php');
					 */
					learndash_get_template_part(
						'course/listing.php',
						array(
							'course_id'            => $course_id,
							'user_id'              => $user_id,
							'lessons'              => $lessons,
							'lesson_topics'        => $lesson_topics,
							'quizzes'              => $quizzes,
							'has_access'           => $has_access,
							'course_pager_results' => $course_pager_results,
							'lesson_progression_enabled' => $lesson_progression_enabled,
						),
						true
					);

					/**
					 * Fires before the course content listing.
					 *
					 * @since BuddyBoss 2.9.00
					 *
					 * @param int $course_id Course ID.
					 * @param int $user_id   User ID.
					 */
					do_action( 'learndash-course-content-list-after', $course_id, $user_id );
					?>

				</div> <!--/.ld-item-list-->
			</div>

		</div>
	</div>
</div>
