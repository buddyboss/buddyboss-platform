<?php
/**
 * LearnDash Single Topic Template for ReadyLaunch.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 *
 * Available Variables:
 *
 * $course_id                 : (int) ID of the course
 * $course                    : (object) Post object of the course
 * $course_settings           : (array) Settings specific to current course
 * $course_status             : Course Status
 * $has_access                : User has access to course or is enrolled.
 *
 * $courses_options            : Options/Settings as configured on Course Options page
 * $lessons_options            : Options/Settings as configured on Lessons Options page
 * $quizzes_options            : Options/Settings as configured on Quiz Options page
 *
 * $user_id                    : (object) Current User ID
 * $logged_in                  : (true/false) User is logged in
 * $current_user               : (object) Currently logged in user object
 * $quizzes                    : (array) Quizzes Array
 * $post                       : (object) The topic post object
 * $lesson_post                : (object) Lesson post object in which the topic exists
 * $topics                     : (array) Array of Topics in the current lesson
 * $all_quizzes_completed      : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled : (true/false)
 * $show_content               : (true/false) true if lesson progression is disabled or if previous lesson and topic is completed.
 * $previous_lesson_completed  : (true/false) true if previous lesson is completed
 * $previous_topic_completed   : (true/false) true if previous topic is completed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure LearnDash functions are available.
if ( ! class_exists( 'SFWD_LMS' ) || ! function_exists( 'learndash_get_course_id' ) ) {
	// Fallback to default content if LearnDash functions aren't available.
	?>
	<div class="bb-learndash-content-wrap">
		<main class="bb-learndash-content-area">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-topic' ); ?>>
				<header class="bb-rl-entry-header">
					<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="bb-rl-entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		</main>
	</div>
	<?php
	return;
}

$topic_id       = get_the_ID();
$user_id        = get_current_user_id();
$course_id      = function_exists( 'learndash_get_course_id' ) ? learndash_get_course_id( $topic_id ) : 0;
$lesson_id      = function_exists( 'learndash_get_lesson_id' ) ? learndash_get_lesson_id( $topic_id ) : 0;
$topic          = get_post( $topic_id );
$lesson_post    = get_post( $lesson_id );
$topic_progress = function_exists( 'learndash_topic_progress' ) ? learndash_topic_progress(
	array(
		'topic_id' => $topic_id,
		'user_id'  => $user_id,
		'array'    => true,
	)
) : array();
$is_enrolled    = function_exists( 'sfwd_lms_has_access' ) ? sfwd_lms_has_access( $course_id, $user_id ) : false;
$topic_status   = function_exists( 'learndash_topic_status' ) ? learndash_topic_status( $topic_id, $user_id ) : '';
$topics         = function_exists( 'learndash_get_topic_list' ) ? learndash_get_topic_list( $lesson_id, $course_id ) : array();
$quizzes        = function_exists( 'learndash_get_lesson_quiz_list' ) ? learndash_get_lesson_quiz_list( $topic_id, $user_id, $course_id ) : array();

$lesson_list = learndash_get_course_lessons_list( $course_id, null, array( 'num' => -1 ) );
$lesson_list = array_column( $lesson_list, 'post' );

// Find lesson number.
$lesson_no = 1;
foreach ( $lesson_list as $les ) {
	if ( (int) $les->ID === (int) $lesson_id ) {
		break;
	}
	++$lesson_no;
}

// Find topic number within the lesson.
$topic_no = 1;
foreach ( $topics as $topic_item ) {
	if ( (int) $topic_item->ID === (int) $post->ID ) {
		break;
	}
	++$topic_no;
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--topic">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-topic' ); ?>>
			<div class="bb-rl-topic-block bb-rl-lms-inner-block">
				<header class="bb-rl-entry-header">
					<div class="bb-rl-heading">
						<div class="bb-rl-topic-count bb-rl-lms-inner-count">
							<span class="bb-pages">
								<?php echo esc_html( LearnDash_Custom_Label::get_label( 'lesson' ) ); ?> <?php echo esc_html( $lesson_no ); ?>, 
								<?php echo esc_html( LearnDash_Custom_Label::get_label( 'topic' ) ); ?> <?php echo esc_html( $topic_no ); ?>
								<span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?><?php echo count( $topics ); ?></span>
							</span>
						</div>
						<div class="bb-rl-topic-title">
							<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
						</div>
					</div>

					<?php if ( has_post_thumbnail() ) : ?>
						<div class="bb-rl-topic-featured-image">
							<?php the_post_thumbnail( 'full' ); ?>
						</div>
					<?php endif; ?>

					<div class="bb-rl-topic-meta">
						<?php if ( $is_enrolled ) : ?>
							<div class="bb-rl-topic-status">
								<span class="bb-rl-status bb-rl-enrolled"><?php echo esc_html( $topic_status ); ?></span>
								<?php if ( ! empty( $topic_progress ) ) : ?>
									<div class="bb-rl-topic-progress">
										<div class="bb-rl-progress-bar">
											<div class="bb-rl-progress" style="width: <?php echo (int) $topic_progress['percentage']; ?>%"></div>
										</div>
										<span class="bb-rl-percentage"><?php echo (int) $topic_progress['percentage']; ?>% <?php esc_html_e( 'Complete', 'buddyboss' ); ?></span>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</header>

				<?php
				$buddyboss_content = apply_filters( 'buddyboss_learndash_content', '', $post );
				if ( ! empty( $buddyboss_content ) ) {
					echo wp_kses_post( $buddyboss_content );
				} else {
					?>
					<div class="bb-rl-entry-content bb-rl-topic-entry">
						<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
							<?php
							// Implement lesson progression logic.
							$lesson_progression_enabled = function_exists( 'learndash_lesson_progression_enabled' ) ? learndash_lesson_progression_enabled( $course_id ) : false;
							$show_content               = true;

							if ( ! empty( $lesson_progression_enabled ) ) :
								$last_incomplete_step = function_exists( 'learndash_is_topic_accessable' ) ? learndash_is_topic_accessable( $user_id, $post, true, $course_id ) : false;

								if ( ! empty( $user_id ) ) {
									if ( function_exists( 'learndash_user_progress_is_step_complete' ) && learndash_user_progress_is_step_complete( $user_id, $course_id, $post->ID ) ) {
										$show_content = true;
									} else {
										$bypass_course_limits_admin_users = function_exists( 'learndash_can_user_bypass' ) ? learndash_can_user_bypass( $user_id, 'learndash_topic_progression' ) : false;
										if ( $bypass_course_limits_admin_users ) {
											remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );
											$previous_lesson_completed = true;
										} else {
											$previous_step_post_id = function_exists( 'learndash_user_progress_get_parent_incomplete_step' ) ? learndash_user_progress_get_parent_incomplete_step( $user_id, $course_id, $post->ID ) : 0;
											if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $post->ID ) ) {
												$previous_lesson_completed = false;
												$last_incomplete_step      = get_post( $previous_step_post_id );
											} else {
												$previous_step_post_id     = function_exists( 'learndash_user_progress_get_previous_incomplete_step' ) ? learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID ) : 0;
												$previous_lesson_completed = true;
												if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $post->ID ) ) {
													$previous_lesson_completed = false;
													$last_incomplete_step      = get_post( $previous_step_post_id );
												}
											}

											/**
											 * Filter to override previous step completed.
											 *
											 * @since BuddyBoss 2.9.00
											 *
											 * @param bool $previous_lesson_completed True if previous step completed.
											 * @param int  $step_id                   Step Post ID.
											 * @param int  $user_id                   User ID.
											 */
											$previous_lesson_completed = apply_filters( 'learndash_previous_step_completed', $previous_lesson_completed, $post->ID, $user_id );
										}

										$show_content = $previous_lesson_completed;
									}

									// Check for sample topics.
									if ( function_exists( 'learndash_is_sample' ) && learndash_is_sample( $post ) ) {
										$show_content = true;
									}

									// Handle blocked content.
									if (
										$last_incomplete_step &&
										$last_incomplete_step instanceof WP_Post &&
										(
											! ( function_exists( 'learndash_is_sample' ) && learndash_is_sample( $post ) ) ||
											(bool) $is_enrolled
										)
									) {
										$show_content = false;

										$sub_context = '';
										if ( 'on' === learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_enabled' ) ) {
											if ( ! empty( learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_url' ) ) ) {
												if ( 'BEFORE' === learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_shown' ) ) {
													if ( ! learndash_video_complete_for_step( $last_incomplete_step->ID, $course_id, $user_id ) ) {
														$sub_context = 'video_progression';
													}
												}
											}
										}

										/**
										 * Fires before the topic progression.
										 *
										 * @since BuddyBoss 2.9.00
										 *
										 * @param int $topic_id  Topic ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-topic-progression-before', $post->ID, $course_id, $user_id );

										if ( function_exists( 'learndash_get_template_part' ) ) {
											learndash_get_template_part(
												'modules/messages/lesson-progression.php',
												array(
													'previous_item' => $last_incomplete_step,
													'course_id'     => $course_id,
													'user_id'       => $user_id,
													'context'       => 'topic',
													'sub_context'   => $sub_context,
												),
												true
											);
										}

										/**
										 * Fires after the topic progression.
										 *
										 * @since BuddyBoss 2.9.00
										 *
										 * @param int $topic_id  Topic ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-topic-progression-after', $post->ID, $course_id, $user_id );
									}
								} else {
									$show_content = true;
								}
							else :
								$show_content = true;
							endif;

							if ( $show_content ) :
								if ( function_exists( 'learndash_get_template_part' ) ) {
									$materials = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $post, 'topic_materials' ) : '';
									learndash_get_template_part(
										'modules/tabs.php',
										array(
											'course_id' => $course_id,
											'post_id'   => get_the_ID(),
											'user_id'   => $user_id,
											'content'   => $content,
											'materials' => $materials,
											'context'   => 'topic',
										),
										true
									);
								} else {
									the_content();
								}
							endif;
							?>
						</div>
					</div>

					<?php
				}
				/**
				 * Display Topic Assignments
				 */
				if ( function_exists( 'learndash_lesson_hasassignments' ) && learndash_lesson_hasassignments( $post ) && ! empty( $user_id ) ) :
					$bypass_course_limits_admin_users = function_exists( 'learndash_can_user_bypass' ) ? learndash_can_user_bypass( $user_id, 'learndash_lesson_assignment' ) : false;
					$course_children_steps_completed  = function_exists( 'learndash_user_is_course_children_progress_complete' ) ? learndash_user_is_course_children_progress_complete( $user_id, $course_id, $post->ID ) : false;
					$lesson_progression_enabled       = function_exists( 'learndash_lesson_progression_enabled' ) ? learndash_lesson_progression_enabled() : false;

					if ( ( $lesson_progression_enabled && $course_children_steps_completed ) || ! $lesson_progression_enabled || $bypass_course_limits_admin_users ) :
						?>
						<div class="bb-rl-topic-assignments bb-rl-assignments-module">
							<?php
							/**
							 * Fires before the lesson assignment.
							 *
							 * @since BuddyBoss 2.9.00
							 *
							 * @param int $post_id   Post ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-lesson-assignment-before', get_the_ID(), $course_id, $user_id );

							if ( function_exists( 'learndash_get_template_part' ) ) {
								learndash_get_template_part(
									'assignment/listing.php',
									array(
										'user_id'          => $user_id,
										'course_step_post' => $post,
										'course_id'        => $course_id,
										'context'          => 'topic',
									),
									true
								);
							}

							/**
							 * Fires after the lesson assignment.
							 *
							 * @since BuddyBoss 2.9.00
							 *
							 * @param int $post_id   Post ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-lesson-assignment-after', get_the_ID(), $course_id, $user_id );
							?>
						</div>
						<?php
					endif;
				endif;
				?>

				<?php
				if ( ! empty( $quizzes ) ) {
					?>
					<div class="bb-rl-topic-quizzes bb-rl-lms-inner-content-block">
						<h3><?php esc_html_e( 'Topic Quizzes', 'buddyboss' ); ?></h3>
						<div class="bb-rl-ld-lesson-list bb-rl-ld-lesson-list--snippet">
							<?php
							foreach ( $quizzes as $quiz ) {
								learndash_get_template_part(
									'quiz/partials/row.php',
									array(
										'quiz'      => $quiz,
										'context'   => 'topic',
										'course_id' => $course_id,
										'user_id'   => $user_id,
									),
									true
								);
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<div class="bb-rl-lms-content-comments bb-rl-course-content-comments">
				<?php
				// If comments are open or we have at least one comment, load up the comment template.
				$focus_mode         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );
				$post_type_comments = learndash_post_type_supports_comments( $post->post_type );
				if ( is_user_logged_in() && 'yes' === $focus_mode && comments_open() ) {
					learndash_get_template_part(
						'focus/comments.php',
						array(
							'course_id' => $course_id,
							'user_id'   => $user_id,
							'context'   => 'focus',
						),
						true
					);
				} elseif ( true === $post_type_comments ) {
					if ( comments_open() ) :
						bp_get_template_part( 'learndash/ld30/comments' );
					endif;
				}
				?>
			</div>

			<?php
			$can_complete = false;
			if ( $all_quizzes_completed && $logged_in && ! empty( $course_id ) ) :
				$can_complete = apply_filters( 'learndash-topic-can-complete', true, get_the_ID(), $course_id, $user_id );
			endif;
			learndash_get_template_part(
				'modules/course-steps.php',
				array(
					'course_id'             => $course_id,
					'course_step_post'      => $post,
					'all_quizzes_completed' => $all_quizzes_completed,
					'user_id'               => $user_id,
					'course_settings'       => isset( $course_settings ) ? $course_settings : array(),
					'context'               => 'topic',
					'can_complete'          => $can_complete,
				),
				true
			);
			?>
		</article>
	</main>
</div>
