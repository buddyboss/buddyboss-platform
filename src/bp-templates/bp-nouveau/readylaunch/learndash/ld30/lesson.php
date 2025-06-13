<?php
/**
 * LearnDash Single Lesson Template for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
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
$lesson          = get_post( $lesson_id );
$lesson_progress = function_exists( 'learndash_lesson_progress' ) ? learndash_lesson_progress(
	array(
		'lesson_id' => $lesson_id,
		'user_id'   => $user_id,
		'array'     => true,
	)
) : array();
$is_enrolled     = function_exists( 'sfwd_lms_has_access' ) && sfwd_lms_has_access( $course_id, $user_id );
$lesson_status   = function_exists( 'learndash_lesson_status' ) && learndash_lesson_status( $lesson_id, $user_id );
$topics          = function_exists( 'learndash_get_topic_list' ) ? learndash_get_topic_list( $lesson_id, $user_id ) : array();
$prev_lesson     = function_exists( 'learndash_get_previous_lesson' ) && learndash_get_previous_lesson( $lesson_id );
$next_lesson     = function_exists( 'learndash_get_next_lesson' ) && learndash_get_next_lesson( $lesson_id );

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

// Define variables for course-steps module compatibility.
$logged_in                 = is_user_logged_in();
$course_settings           = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $course_id ) : array();
$all_quizzes_completed     = true; // Assume all quizzes are completed for now.
$previous_lesson_completed = true;
if ( function_exists( 'learndash_is_lesson_accessable' ) ) {
	$previous_lesson_completed = learndash_is_lesson_accessable( $user_id, $post );
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--lesson">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-lesson' ); ?>>
			<div class="bb-rl-lesson-block bb-rl-lms-inner-block">
				<header class="bb-rl-entry-header">
					<div class="bb-rl-heading">
						<div class="bb-rl-lesson-count bb-rl-lms-inner-count">
							<span class="bb-pages"><?php echo esc_html( LearnDash_Custom_Label::get_label( 'lesson' ) ); ?> <?php echo esc_html( $lesson_no ); ?> <span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?><?php echo esc_html( count( $lesson_list ) ); ?></span></span>
						</div>
						<div class="bb-rl-lesson-title">
							<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
						</div>
					</div>

					<div class="bb-rl-lesson-meta">
						<?php if ( $is_enrolled ) : ?>
							<div class="bb-rl-lesson-status">
								<span class="bb-rl-status bb-rl-enrolled"><?php echo esc_html( $lesson_status ); ?></span>
								<?php if ( ! empty( $lesson_progress ) ) : ?>
									<div class="bb-rl-lesson-progress">
										<div class="bb-rl-progress-bar">
											<div class="bb-rl-progress" style="width: <?php echo (int) $lesson_progress['percentage']; ?>%"></div>
										</div>
										<span class="bb-rl-percentage"><?php echo (int) $lesson_progress['percentage']; ?>% <?php esc_html_e( 'Complete', 'buddyboss' ); ?></span>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</header>

				<div class="bb-rl-entry-content bb-rl-lesson-entry">
					<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
						<?php
						/**
						 * Fires before the lesson content starts.
						 *
						 * @since 3.0.0
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
							$last_incomplete_step = function_exists( 'learndash_is_lesson_accessable' ) ? learndash_is_lesson_accessable( $user_id, $post, true, $course_id ) : false;

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
										 * @since BuddyBoss [BBVERSION]
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
									 * @since BuddyBoss [BBVERSION]
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
												'course_id' => $course_id,
												'user_id' => $user_id,
												'context' => 'lesson',
												'sub_context' => $sub_context,
											),
											true
										);
									}

									/**
									 * Fires after the lesson progression.
									 *
									 * @since BuddyBoss [BBVERSION]
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
							// Load tabs if available.
							if ( function_exists( 'learndash_get_template_part' ) ) {
								$materials = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $post, 'lesson_materials' ) : '';
								learndash_get_template_part(
									'modules/tabs.php',
									array(
										'course_id' => $course_id,
										'post_id'   => $post->ID,
										'user_id'   => $user_id,
										'content'   => $content,
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
						 * @since BuddyBoss [BBVERSION]
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
				if ( function_exists( 'learndash_lesson_hasassignments' ) && learndash_lesson_hasassignments( $post ) && ! empty( $user_id ) ) :
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
							 * @since BuddyBoss [BBVERSION]
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
							 * @since BuddyBoss [BBVERSION]
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

				<?php if ( ! empty( $topics ) ) : ?>
					<div class="bb-rl-lesson-topics">
						<h3><?php esc_html_e( 'Lesson Topics', 'buddyboss' ); ?></h3>
						<ul class="bb-rl-topics-list">
							<?php
							foreach ( $topics as $topic ) :
								$topic_link = get_permalink( $topic->ID );
								?>
								<li class="bb-rl-topic-item">
									<a href="<?php echo esc_url( $topic_link ); ?>" class="bb-rl-topic-link">
										<?php echo esc_html( $topic->post_title ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>

			<nav class="bb-rl-ld-module-footer bb-rl-lesson-footer">
				<div class="bb-rl-ld-module-actions bb-rl-lesson-actions">
					<div class="bb-rl-course-steps">
						<button type="submit" class="bb-rl-mark-complete-button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><?php esc_html_e( 'Mark Complete', 'buddyboss' ); ?></button>
					</div>
					<div class="bb-rl-ld-module-count bb-rl-lesson-count">
						<span class="bb-pages"><?php echo esc_html( LearnDash_Custom_Label::get_label( 'lesson' ) ); ?> <?php echo esc_html( $lesson_no ); ?> <span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?><?php echo esc_html( count( $lesson_list ) ); ?></span></span>
					</div>
					<div class="learndash_next_prev_link">
						<?php
						if ( isset( $pagination_urls['prev'] ) && '' !== $pagination_urls['prev'] ) {
							echo esc_html( $pagination_urls['prev'] );
						} else {
							echo '<span class="prev-link empty-post"><i class="bb-icons-rl-caret-left"></i>' . esc_html__( 'Previous', 'buddyboss' ) . '</span>';
						}
						if (
							(
								isset( $pagination_urls['next'] ) &&
								apply_filters( 'learndash_show_next_link', learndash_is_lesson_complete( $user_id, $post->ID ), $user_id, $post->ID ) &&
								'' !== $pagination_urls['next']
							) ||
							(
								isset( $pagination_urls['next'] ) &&
								'' !== $pagination_urls['next'] &&
								isset( $course_settings['course_disable_lesson_progression'] ) &&
								'on' === $course_settings['course_disable_lesson_progression']
							)
						) {
							echo esc_html( $pagination_urls['next'] );
						} else {
							echo '<span class="next-link empty-post">' . esc_html__( 'Next Lesson', 'buddyboss' ) . '<i class="bb-icons-rl-caret-right"></i></span>';
						}
						?>
					</div>
				</div>
			</nav>
		</article>
	</main>
</div> 