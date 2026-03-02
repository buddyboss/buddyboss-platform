<?php
/**
 * LearnDash Single Quiz Template for ReadyLaunch
 *
 * Available Variables:
 *
 * $course_id                   : (int) ID of the course
 * $course                      : (object) Post object of the course
 * $course_settings             : (array) Settings specific to current course
 * $course_status               : Course Status
 * $has_access                  : User has access to course or is enrolled.
 *
 * $courses_options             : Options/Settings as configured on Course Options page
 * $lessons_options             : Options/Settings as configured on Lessons Options page
 * $quizzes_options             : Options/Settings as configured on Quiz Options page
 *
 * $user_id                     : (object) Current User ID
 * $logged_in                   : (true/false) User is logged in
 * $current_user                : (object) Currently logged in user object
 * $post                        : (object) The quiz post object () (Deprecated in LD 3.1. User $quiz_post instead).
 * $quiz_post                   : (object) The quiz post object ().
 * $lesson_progression_enabled  : (true/false)
 * $show_content                : (true/false) true if user is logged in and lesson progression is disabled or if
 * previous lesson and topic is completed.
 * $attempts_left               : (true/false)
 * $attempts_count              : (integer) No of attempts already made
 * $quiz_settings               : (array)
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set up global post context for LearnDash.
global $post;
if ( ! isset( $post ) ) {
	$post = get_post( get_the_ID() );
}

// Ensure we have the quiz_post variable.
if ( ( ! isset( $quiz_post ) ) || ( ! is_a( $quiz_post, 'WP_Post' ) ) ) {
	$quiz_post = $post;
}

// Initialize variables if not already set by LearnDash.
if ( ! isset( $course_id ) || empty( $course_id ) ) {
	$course_id = function_exists( 'learndash_get_course_id' ) ? learndash_get_course_id( $quiz_post->ID ) : 0;
}

if ( ! isset( $user_id ) ) {
	$user_id = get_current_user_id();
}

if ( ! isset( $lesson_progression_enabled ) ) {
	$lesson_progression_enabled = function_exists( 'learndash_lesson_progression_enabled' ) ? learndash_lesson_progression_enabled( $course_id ) : false;
}

if ( ! isset( $logged_in ) ) {
	$logged_in = is_user_logged_in();
}

if ( ! isset( $current_user ) ) {
	$current_user = wp_get_current_user();
}

if ( ! isset( $has_access ) ) {
	$has_access = function_exists( 'sfwd_lms_has_access' ) ? sfwd_lms_has_access( $course_id, $user_id ) : false;
}

if ( ! isset( $attempts_left ) ) {
	$attempts_left = function_exists( 'learndash_quiz_attempts_left' ) ? learndash_quiz_attempts_left( $user_id, $quiz_post->ID ) : -1;
}

if ( ! isset( $attempts_count ) ) {
	$quiz_attempts  = function_exists( 'learndash_get_user_quiz_attempts' ) ? learndash_get_user_quiz_attempts( $user_id, $quiz_post->ID ) : array();
	$attempts_count = is_array( $quiz_attempts ) ? count( $quiz_attempts ) : 0;
}

if ( ! isset( $content ) ) {
	$content = apply_filters( 'the_content', $quiz_post->post_content );
}

if ( ! isset( $materials ) ) {
	$materials = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $quiz_post, 'lesson_materials' ) : '';
}

// Additional LearnDash required variables.
if ( ! isset( $courses_options ) ) {
	$courses_options = function_exists( 'learndash_get_option' ) ? learndash_get_option( 'sfwd-courses' ) : array();
}

if ( ! isset( $lessons_options ) ) {
	$lessons_options = function_exists( 'learndash_get_option' ) ? learndash_get_option( 'sfwd-lessons' ) : array();
}

if ( ! isset( $quizzes_options ) ) {
	$quizzes_options = function_exists( 'learndash_get_option' ) ? learndash_get_option( 'sfwd-quiz' ) : array();
}

if ( ! isset( $quiz_settings ) ) {
	$quiz_settings = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $quiz_post ) : array();
}

// ReadyLaunch specific variables for pagination.
$lesson_list         = learndash_get_course_lessons_list( $course_id, null, array( 'num' => -1 ) );
$lesson_list         = array_column( $lesson_list, 'post' );
$course_quizzes_list = function_exists( 'learndash_get_course_quiz_list' ) ? learndash_get_course_quiz_list( $course_id, $user_id ) : array();

$content_urls    = array();
$quiz_urls       = array();
$pagination_urls = array(
	'prev' => '',
	'next' => '',
);
$current_quiz_no = 1;

// Use ReadyLaunch helper if available.
if ( class_exists( 'BB_Readylaunch_Learndash_Helper' ) ) {
	$bb_rl_helper    = BB_Readylaunch_Learndash_Helper::instance();
	$content_urls    = $bb_rl_helper->bb_rl_ld_custom_pagination( $course_id, $lesson_list, $course_quizzes_list );
	$quiz_urls       = $bb_rl_helper->bb_rl_ld_custom_quiz_count( $course_id, $lesson_list, $course_quizzes_list );
	$pagination_urls = $bb_rl_helper->bb_rl_custom_next_prev_url( $content_urls );
	$current_quiz_no = $bb_rl_helper->bb_rl_ld_custom_quiz_key( $quiz_urls );
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--quiz">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-quiz' ); ?>>
			<div class="bb-rl-quiz-block">
				<header class="bb-rl-entry-header">
					<div class="bb-rl-heading">
						<div class="bb-rl-quiz-count bb-rl-lms-inner-count">
							<span class="bb-pages">
								<?php
								if ( function_exists( 'LearnDash_Custom_Label::get_label' ) ) {
									echo esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) );
								} else {
									esc_html_e( 'Quiz', 'buddyboss' );
								}
								?>
								<?php echo esc_html( $current_quiz_no ); ?>
								<span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?><?php echo is_array( $quiz_urls ) ? count( $quiz_urls ) : 1; ?></span>
							</span>
						</div>
						<div class="bb-rl-quiz-title">
							<h1 class="bb-rl-entry-title"><?php echo esc_html( $quiz_post->post_title ); ?></h1>
						</div>
					</div>

					<?php if ( has_post_thumbnail( $quiz_post->ID ) ) : ?>
						<div class="bb-rl-quiz-featured-image">
							<?php echo get_the_post_thumbnail( $quiz_post->ID, 'full' ); ?>
						</div>
					<?php endif; ?>

					<div class="bb-rl-quiz-meta">
						<?php if ( $has_access ) : ?>
							<div class="bb-rl-quiz-status">
								<?php
								$quiz_completed = function_exists( 'learndash_is_quiz_complete' ) ? learndash_is_quiz_complete( $user_id, $quiz_post->ID ) : false;
								$status_text    = $quiz_completed ? esc_html__( 'Completed', 'buddyboss' ) : esc_html__( 'Not Started', 'buddyboss' );
								if ( $attempts_count > 0 && ! $quiz_completed ) {
									$status_text = esc_html__( 'In Progress', 'buddyboss' );
								}
								?>
								<span class="bb-rl-status bb-rl-enrolled"><?php echo esc_html( $status_text ); ?></span>

								<?php if ( $attempts_count > 0 ) : ?>
									<div class="bb-rl-quiz-attempts">
										<span class="bb-rl-attempts-count">
											<?php
											printf(
											/* translators: placeholders: Attempts Count. */
												esc_html__( 'Attempts: %d', 'buddyboss' ),
												(int) $attempts_count
											);
											?>
										</span>
										<?php if ( $attempts_left > 0 ) : ?>
											<span class="bb-rl-attempts-left">
												<?php
												printf(
												/* translators: placeholders: Attempts Left. */
													esc_html__( '(%d remaining)', 'buddyboss' ),
													(int) $attempts_left
												);
												?>
											</span>
										<?php elseif ( 0 === (int) $attempts_left ) : ?>
											<span class="bb-rl-attempts-exhausted">
												<?php esc_html_e( '(No attempts remaining)', 'buddyboss' ); ?>
											</span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</header>

				<?php
				$buddyboss_content = apply_filters( 'buddyboss_learndash_content', '', $quiz_post );
				if ( ! empty( $buddyboss_content ) ) {
					echo wp_kses_post( $buddyboss_content );
				} else {
					?>
					<div class="bb-rl-entry-content bb-rl-quiz-entry">
						<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
							<?php
							/**
							 * Fires before the quiz content starts.
							 *
							 * @since BuddyBoss 2.9.00
							 *
							 * @param int $quiz_id   Quiz ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-quiz-before', $quiz_post->ID, $course_id, $user_id );

							// Load infobar.
							if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
								$shown_content_key = 'learndash-shortcode-wrap-ld_infobar-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
								if ( false === strstr( $content, $shown_content_key ) ) {
									$shortcode_out = do_shortcode( '[ld_infobar course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
									if ( ! empty( $shortcode_out ) ) {
										echo $shortcode_out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content
									}
								}
							} elseif ( function_exists( 'learndash_get_template_part' ) ) {
								learndash_get_template_part(
									'modules/infobar.php',
									array(
										'context'   => 'quiz',
										'course_id' => $course_id,
										'user_id'   => $user_id,
										'post'      => $quiz_post,
									),
									true
								);
							}

							// Implement lesson progression logic.
							if ( ! empty( $lesson_progression_enabled ) ) :
								$last_incomplete_step = function_exists( 'learndash_is_quiz_accessable' ) ? learndash_is_quiz_accessable( $user_id, $quiz_post, true, $course_id ) : false;

								if ( ! empty( $user_id ) ) {
									if ( function_exists( 'learndash_user_progress_is_step_complete' ) && learndash_user_progress_is_step_complete( $user_id, $course_id, $quiz_post->ID ) ) {
										$show_content = true;
									} else {
										$bypass_course_limits_admin_users = isset( $bypass_course_limits_admin_users ) ? $bypass_course_limits_admin_users : false;
										if ( $bypass_course_limits_admin_users ) {
											remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );
											$previous_lesson_completed = true;
										} else {
											$previous_step_post_id = function_exists( 'learndash_user_progress_get_parent_incomplete_step' ) ? learndash_user_progress_get_parent_incomplete_step( $user_id, $course_id, $quiz_post->ID ) : 0;
											if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $quiz_post->ID ) ) {
												$previous_lesson_completed = false;
												$last_incomplete_step      = get_post( $previous_step_post_id );
											} else {
												$previous_step_post_id     = function_exists( 'learndash_user_progress_get_previous_incomplete_step' ) ? learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $quiz_post->ID ) : 0;
												$previous_lesson_completed = true;
												if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $quiz_post->ID ) ) {
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
											$previous_lesson_completed = apply_filters( 'learndash_previous_step_completed', $previous_lesson_completed, $quiz_post->ID, $user_id );
										}

										$show_content = $previous_lesson_completed;
									}

									// Check for sample quizzes.
									if ( function_exists( 'learndash_is_sample' ) && learndash_is_sample( $quiz_post ) ) {
										$show_content = true;
									}

									// Handle blocked content.
									if (
										$last_incomplete_step &&
										$last_incomplete_step instanceof WP_Post &&
										(
											! ( function_exists( 'learndash_is_sample' ) && learndash_is_sample( $quiz_post ) )
											|| (bool) $has_access
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
										 * Fires before the quiz progression.
										 *
										 * @since BuddyBoss 2.9.00
										 *
										 * @param int $quiz_id   Quiz ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-quiz-progression-before', $quiz_post->ID, $course_id, $user_id );

										if ( function_exists( 'learndash_get_template_part' ) ) {
											learndash_get_template_part(
												'modules/messages/lesson-progression.php',
												array(
													'previous_item' => $last_incomplete_step,
													'course_id'     => $course_id,
													'user_id'       => $user_id,
													'context'       => 'quiz',
													'sub_context'   => $sub_context,
												),
												true
											);
										}

										/**
										 * Fires after the quiz progress.
										 *
										 * @since BuddyBoss 2.9.00
										 *
										 * @param int $quiz_id   Quiz ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-quiz-progression-after', $quiz_post->ID, $course_id, $user_id );
									}
								} else {
									$show_content = true;
								}
							else :
								$show_content = true;
							endif;

							if ( $show_content ) :
								// Load tabs.
								if ( function_exists( 'learndash_get_template_part' ) ) {
									learndash_get_template_part(
										'modules/tabs.php',
										array(
											'course_id' => $course_id,
											'post_id'   => $quiz_post->ID,
											'user_id'   => $user_id,
											'content'   => $content,
											'materials' => $materials,
											'context'   => 'quiz',
										),
										true
									);
								}

								if ( $attempts_left ) :
									/**
									 * Fires before the actual quiz content (not WP_Editor content).
									 *
									 * @since BuddyBoss 2.9.00
									 *
									 * @param int $quiz_id   Quiz ID.
									 * @param int $course_id Course ID.
									 * @param int $user_id   User ID.
									 */
									do_action( 'learndash-quiz-actual-content-before', $quiz_post->ID, $course_id, $user_id );

									// Generate quiz content if not already set.
									if ( ! isset( $quiz_content ) || empty( $quiz_content ) ) {
										// Get quiz pro ID.
										$quiz_pro_id = get_post_meta( $quiz_post->ID, 'quiz_pro_id', true );
										$quiz_pro_id = absint( $quiz_pro_id );
										if ( empty( $quiz_pro_id ) ) {
											if ( isset( $quiz_settings['quiz_pro'] ) ) {
												$quiz_settings['quiz_pro'] = absint( $quiz_settings['quiz_pro'] );
												if ( ! empty( $quiz_settings['quiz_pro'] ) ) {
													$quiz_pro_id = $quiz_settings['quiz_pro'];
												}
											}
										}

										if ( ! empty( $quiz_pro_id ) ) {
											// Generate quiz content.
											$quiz_content = wptexturize(
												do_shortcode( '[LDAdvQuiz ' . $quiz_pro_id . ' quiz_pro_id="' . $quiz_pro_id . '" quiz_id="' . $quiz_post->ID . '" course_id="' . $course_id . '" lesson_id="' . ( isset( $quiz_settings['lesson'] ) ? $quiz_settings['lesson'] : '' ) . '" topic_id="' . ( isset( $quiz_settings['topic'] ) ? $quiz_settings['topic'] : '' ) . '"]' )
											);

											// Apply the LearnDash filter.
											$quiz_content = apply_filters( 'learndash_quiz_content', $quiz_content, $quiz_post );
										}
									}

									if ( isset( $quiz_content ) && ! empty( $quiz_content ) ) {
										echo $quiz_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content
									}

									/**
									 * Fires after the actual quiz content (not WP_Editor content).
									 *
									 * @since BuddyBoss 2.9.00
									 *
									 * @param int $quiz_id   Quiz ID.
									 * @param int $course_id Course ID.
									 * @param int $user_id   User ID.
									 */
									do_action( 'learndash-quiz-actual-content-after', $quiz_post->ID, $course_id, $user_id );
								else :
									/**
									 * Display an alert for exhausted attempts
									 */
									echo '<div class="bb-rl-alert bb-rl-alert--warning">
											<div class="bb-rl-alert-content">
												<h3>' . esc_html__( 'Quiz Attempts Exhausted', 'buddyboss' ) . '</h3>
												<p>' . sprintf(
												/* translators: placeholders: Quiz Count. */
													esc_html__( 'You have already taken this %1$s %2$d time(s) and may not take it again.', 'buddyboss' ),
													function_exists( 'learndash_get_custom_label_lower' ) ? esc_html( learndash_get_custom_label_lower( 'quiz' ) ) : 'quiz',
													(int) $attempts_count
												) . '</p>
											</div>
										</div>';
								endif;
							endif;

							/**
							 * Fires after the quiz content starts.
							 *
							 * @since BuddyBoss 2.9.00
							 *
							 * @param int $quiz_id   Quiz ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-quiz-after', $quiz_post->ID, $course_id, $user_id );
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
				$post_type_comments = learndash_post_type_supports_comments( $quiz_post->post_type );
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
			if ( learndash_course_steps_is_external( $quiz_post->ID ) ) {
				learndash_get_template_part(
					'modules/course-steps.php',
					array(
						'course_id'        => $course_id,
						'course_step_post' => $quiz_post,
						'user_id'          => $user_id,
						'course_settings'  => ! empty( $course_settings ) ? $course_settings : array(),
						'can_complete'     => ! learndash_course_steps_is_external_attendance_required( $quiz_post->ID ),
						'context'          => 'quiz',
					),
					true
				);
			}
			?>
		</article>
	</main>
</div>
