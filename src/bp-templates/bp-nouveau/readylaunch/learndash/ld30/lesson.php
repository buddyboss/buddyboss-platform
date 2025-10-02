<?php
/**
 * LearnDash Single Lesson Template for ReadyLaunch.
 *
 * Available Variables:
 *
 * $course_id                  : (int) ID of the course
 * $course                     : (object) Post object of the course
 * $course_settings            : (array) Settings specific to current course
 * $course_status              : Course Status
 * $has_access                 : User has access to course or is enrolled.
 *
 * $courses_options            : Options/Settings as configured on Course Options page
 * $lessons_options            : Options/Settings as configured on Lessons Options page
 * $quizzes_options            : Options/Settings as configured on Quiz Options page
 *
 * $user_id                    : (object) Current User ID
 * $logged_in                  : (true/false) User is logged in
 * $current_user               : (object) Currently logged in user object
 *
 * $quizzes                    : (array) Quizzes Array
 * $post                       : (object) The lesson post object
 * $topics                     : (array) Array of Topics in the current lesson
 * $all_quizzes_completed      : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled : (true/false)
 * $show_content               : (true/false) true if lesson progression is disabled or if previous lesson is completed.
 * $previous_lesson_completed  : (true/false) true if previous lesson is completed
 * $lesson_settings            : Settings specific to the current lesson.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure LearnDash functions are available.
global $post;

// Ensure $user_id is defined.
$user_id = isset( $user_id ) ? $user_id : get_current_user_id();

// Ensure $course_id is defined.
$course_id = isset( $course_id ) ? $course_id : 0;

// Get course_id properly.
if ( empty( $course_id ) ) {
	$course_id = learndash_get_course_id( $post->ID );
	if ( empty( $course_id ) ) {
		$course_id = buddyboss_theme()->learndash_helper()->ld_30_get_course_id( $post->ID );
	}
}

// Initialize LearnDash variables.
if ( ! isset( $lesson_progression_enabled ) ) {
	$lesson_progression_enabled = learndash_lesson_progression_enabled();
}

if ( ! isset( $show_content ) ) {
	// Use LearnDash's model to determine if content should be visible.
	if ( class_exists( 'LearnDash\Core\Models\Lesson' ) ) {
		$model        = \LearnDash\Core\Models\Lesson::create_from_post( $post );
		$show_content = $model->is_content_visible( $user_id );
	} else {
		$show_content = true; // Fallback.
	}
}

$content               = isset( $content ) ? $content : '';
$materials             = isset( $materials ) ? $materials : '';
$all_quizzes_completed = isset( $all_quizzes_completed ) ? $all_quizzes_completed : true;
$logged_in             = isset( $logged_in ) ? $logged_in : is_user_logged_in();

// If content is empty, populate it with the lesson content.
if ( empty( $content ) ) {
	$content = get_post_field( 'post_content', $post->ID );

	// Process the content through LearnDash's content processing.
	if ( ! empty( $content ) ) {
		// Apply LearnDash content filters.
		$content = apply_filters( 'learndash_content', $content, $post );
		$content = apply_filters( 'the_content', $content );
	}
}

// Initialize progression variables IMMEDIATELY.
if ( ! isset( $previous_lesson_completed ) ) {
	// Initialize progression variables based on LearnDash's logic.
	$previous_lesson_completed = true; // Default to true (no previous lesson to complete).

	if ( ! empty( $user_id ) ) {
		$current_step_complete = learndash_user_progress_is_step_complete( $user_id, $course_id, $post->ID );

		if ( $current_step_complete ) {
			$previous_lesson_completed = true;
		} elseif ( $lesson_progression_enabled ) {
			// Get the previous item in the course sequence.
			$previous_item = learndash_get_previous( $post );

			if ( ! empty( $previous_item ) ) {
				// There is a previous item, check if it's completed.
				$previous_complete = learndash_is_item_complete( $user_id, $previous_item->ID, $course_id );
				if ( $previous_complete ) {
					$previous_lesson_completed = true;
				} else {
					$previous_lesson_completed = false;
				}
			} else {
				// No previous item found, this is the first lesson.
				$previous_lesson_completed = true;
			}
		} else {
			$previous_lesson_completed = true;
		}
	} else {
		$previous_lesson_completed = true;
	}
}

// Override $show_content based on our progression logic.
if ( $previous_lesson_completed ) {
	$show_content = true;
} else {
	// If previous lesson is not completed, ensure we show the progression notice.
	$show_content = false;
}

if ( ! class_exists( 'SFWD_LMS' ) || ! function_exists( 'learndash_get_course_id' ) ) {
	// Fallback to default content if LearnDash functions aren't available.
	?>
	<div class="bb-learndash-content-wrap">
		<main class="bb-learndash-content-area">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-lesson' ); ?>>
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

$lesson_id       = get_the_ID();
$user_id         = get_current_user_id();
$course_id       = function_exists( 'learndash_get_course_id' ) ? learndash_get_course_id( $lesson_id ) : 0;
$lesson_progress = function_exists( 'learndash_lesson_progress' ) ? learndash_lesson_progress(
	$post,
	$course_id
) : array();
$is_enrolled     = function_exists( 'sfwd_lms_has_access' ) && sfwd_lms_has_access( $course_id, $user_id );

$lesson_list             = learndash_get_course_lessons_list( $course_id, null, array( 'num' => -1 ) );
$lesson_list             = array_column( $lesson_list, 'post' );
$lesson_topics_completed = learndash_lesson_topics_completed( $post->ID );

$lesson_no = 1;
foreach ( $lesson_list as $les ) {
	if ( (int) $les->ID === (int) $post->ID ) {
		break;
	}
	++$lesson_no;
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--lesson">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-lesson' ); ?>>
			<div class="bb-rl-lesson-block bb-rl-lms-inner-block">
				<header class="bb-rl-entry-header">
					<div class="bb-rl-heading">
						<div class="bb-rl-lesson-count bb-rl-lms-inner-count">
							<span class="bb-pages"><?php echo esc_html( LearnDash_Custom_Label::get_label( 'lesson' ) ); ?> <?php echo esc_html( $lesson_no ); ?> <span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo esc_html( count( $lesson_list ) ); ?></span></span>
						</div>
						<div class="bb-rl-lesson-title">
							<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
						</div>
					</div>
				</header>

				<?php
				$buddyboss_content = apply_filters( 'buddyboss_learndash_content', '', $post );
				if ( ! empty( $buddyboss_content ) ) {
					echo wp_kses_post( $buddyboss_content );
				} else {
					?>

					<div class="bb-rl-entry-content bb-rl-lesson-entry">
						<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
							<?php
							/**
							 * Fires before the lesson content starts.
							 *
							 * @since BuddyBoss 2.9.00
							 *
							 * @param int $lesson_id Lesson ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-lesson-before', $lesson_id, $course_id, $user_id );

							// Implement lesson progression logic.
							$lesson_progression_enabled = function_exists( 'learndash_lesson_progression_enabled' ) ? learndash_lesson_progression_enabled( $course_id ) : false;
							$show_content               = true;

							if ( ! empty( $lesson_progression_enabled ) ) :
								$last_incomplete_step = function_exists( 'learndash_user_progress_get_previous_incomplete_step' ) ? learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID, true ) : false;

								if ( ! empty( $user_id ) ) {
									if ( function_exists( 'learndash_user_progress_is_step_complete' ) && learndash_user_progress_is_step_complete( $user_id, $course_id, $post->ID ) ) {
										$show_content = true;
									} else {
										$bypass_course_limits_admin_users = function_exists( 'learndash_can_user_bypass' ) ? learndash_can_user_bypass( $user_id, 'learndash_lesson_progression' ) : false;
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

									// Check for sample lessons.
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
										 * Fires before the lesson progression.
										 *
										 * @since BuddyBoss 2.9.00
										 *
										 * @param int $lesson_id Lesson ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-lesson-progression-before', $post->ID, $course_id, $user_id );

										if ( function_exists( 'learndash_get_template_part' ) ) {
											learndash_get_template_part(
												'modules/messages/lesson-progression.php',
												array(
													'previous_item' => $last_incomplete_step,
													'course_id'     => $course_id,
													'user_id'       => $user_id,
													'context'       => 'lesson',
													'sub_context'   => $sub_context,
												),
												true
											);
										}

										/**
										 * Fires after the lesson progression.
										 *
										 * @since BuddyBoss 2.9.00
										 *
										 * @param int $lesson_id Lesson ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-lesson-progression-after', $post->ID, $course_id, $user_id );
									}
								} else {
									$show_content = true;
								}
							else :
								$show_content = true;
							endif;

							if ( $show_content ) :
								/**
								 * Process video content first
								 */
								$processed_content = $content;

								// Check if video content already exists in the content to prevent duplicate videos.
								$has_video_content = (
									strpos( $content, '<video' ) !== false ||
									strpos( $content, '<iframe' ) !== false ||
									strpos( $content, '[ld_video]' ) !== false
								);

								// Only process video content if it doesn't already exist.
								if ( ! $has_video_content ) {
									// Get video settings for this lesson.
									$lesson_video_enabled = learndash_get_setting( $post, 'lesson_video_enabled' );
									$lesson_video_url     = learndash_get_setting( $post, 'lesson_video_url' );

									if ( 'on' === $lesson_video_enabled && ! empty( $lesson_video_url ) ) {
										// Process video content through LearnDash's video system.
										$video_instance    = Learndash_Course_Video::get_instance();
										$processed_content = $video_instance->add_video_to_content( $content, $post, learndash_get_setting( $post ) );
									}
								}

								// Load tabs if available.
								if ( function_exists( 'learndash_get_template_part' ) ) {
									$materials = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $post, 'lesson_materials' ) : '';
									learndash_get_template_part(
										'modules/tabs.php',
										array(
											'course_id' => $course_id,
											'post_id'   => $post->ID,
											'user_id'   => $user_id,
											'content'   => $processed_content,
											'materials' => $materials,
											'context'   => 'lesson',
										),
										true
									);
								} else {
									the_content();
								}
							endif;

							/**
							 * Fires after the lesson content ends.
							 *
							 * @since BuddyBoss 2.9.00
							 *
							 * @param int $lesson_id Lesson ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-lesson-after', $lesson_id, $course_id, $user_id );
							?>
						</div>
					</div>

					<?php
					/**
					 * Display Lesson Assignments.
					 */
					if ( function_exists( 'learndash_lesson_hasassignments' ) && learndash_lesson_hasassignments( $post ) && ! empty( $user_id ) ) {
						$bypass_course_limits_admin_users = function_exists( 'learndash_can_user_bypass' ) ? learndash_can_user_bypass( $user_id, 'learndash_lesson_assignment' ) : false;
						$course_children_steps_completed  = function_exists( 'learndash_user_is_course_children_progress_complete' ) ? learndash_user_is_course_children_progress_complete( $user_id, $course_id, $post->ID ) : false;
						$lesson_progression_enabled       = function_exists( 'learndash_lesson_progression_enabled' ) ? learndash_lesson_progression_enabled() : false;

						if ( ( $lesson_progression_enabled && $course_children_steps_completed ) || ! $lesson_progression_enabled || $bypass_course_limits_admin_users ) :
							?>
							<div class="bb-rl-lesson-assignments bb-rl-assignments-module">
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
											'course_step_post' => $post,
											'user_id'          => $user_id,
											'course_id'        => $course_id,
											'context'          => 'lesson',
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
					}

					if ( $show_content && ( ! empty( $topics ) || ! empty( $quizzes ) ) ) {
						?>
						<div class="bb-rl-lesson-topics bb-rl-lms-inner-content-block">
							<h3><?php esc_html_e( 'Lesson Topics', 'buddyboss' ); ?></h3>
							<div class="bb-rl-ld-lesson-list bb-rl-ld-lesson-list--snippet">
								<?php
								if ( ! empty( $topics ) ) :
									foreach ( $topics as $topic ) :
										learndash_get_template_part(
											'topic/partials/row.php',
											array(
												'topic'     => $topic,
												'user_id'   => $user_id,
												'course_id' => $course_id,
											),
											true
										);
									endforeach;
								endif;
								?>
							</div>
						</div>
						<?php
					}
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
			/**
			 * Set a variable to switch the next button to complete button
			 *
			 * @var $can_complete [bool] - can the user complete this or not?
			 */
			$can_complete = false;
			if ( $all_quizzes_completed && $logged_in && ! empty( $course_id ) ) {
				$can_complete = $previous_lesson_completed;

				/**
				 * Filters whether a user can complete the lesson or not.
				 *
				 * @since BuddyBoss 2.9.00
				 *
				 * @param boolean $can_complete Whether user can complete lesson or not.
				 * @param int     $post_id      Lesson ID/Topic ID.
				 * @param int     $course_id    Course ID.
				 * @param int     $user_id      User ID.
				 */
				$can_complete = apply_filters( 'learndash-lesson-can-complete', true, get_the_ID(), $course_id, $user_id );
			}
			learndash_get_template_part(
				'modules/course-steps.php',
				array(
					'course_id'        => $course_id,
					'course_step_post' => $post,
					'user_id'          => $user_id,
					'course_settings'  => isset( $course_settings ) ? $course_settings : array(),
					'can_complete'     => $can_complete,
					'context'          => 'lesson',
				),
				true
			);
			?>
		</article>
	</main>
</div>
