<?php
/**
 * LearnDash Single Quiz Template for ReadyLaunch
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @version 1.0.0
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

// Ensure LearnDash functions are available.
if ( ! class_exists( 'SFWD_LMS' ) || ! function_exists( 'learndash_get_course_id' ) ) {
	// Fallback to default content if LearnDash functions aren't available.
	?>
	<div class="bb-learndash-content-wrap">
		<main class="bb-learndash-content-area">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-quiz' ); ?>>
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

// Handle both $post and $quiz_post variables for compatibility
$quiz_id        = get_the_ID();
$quiz_post      = isset( $quiz_post ) ? $quiz_post : get_post( $quiz_id );
$user_id        = get_current_user_id();
$course_id      = function_exists( 'learndash_get_course_id' ) ? learndash_get_course_id( $quiz_id ) : 0;
$lesson_id      = function_exists( 'learndash_get_lesson_id' ) ? learndash_get_lesson_id( $quiz_id ) : 0;
$quiz           = get_post( $quiz_id );
$lesson_post    = $lesson_id ? get_post( $lesson_id ) : null;
$quiz_settings  = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $quiz_post ) : array();
$is_enrolled    = function_exists( 'sfwd_lms_has_access' ) ? sfwd_lms_has_access( $course_id, $user_id ) : false;
$quiz_attempts  = function_exists( 'learndash_get_user_quiz_attempts' ) ? learndash_get_user_quiz_attempts( $user_id, $quiz_id ) : array();
$attempts_count = is_array( $quiz_attempts ) ? count( $quiz_attempts ) : 0;
$attempts_left  = function_exists( 'learndash_quiz_attempts_left' ) ? learndash_quiz_attempts_left( $user_id, $quiz_id ) : -1;

$lesson_list         = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lesson_list         = array_column( $lesson_list, 'post' );
$course_quizzes_list = function_exists( 'learndash_get_course_quiz_list' ) ? learndash_get_course_quiz_list( $course_id, $user_id ) : array();
$content_urls        = bb_load_readylaunch()->learndash_helper()->bb_rl_ld_custom_pagination( $course_id, $lesson_list, $course_quizzes_list );
$quiz_urls           = bb_load_readylaunch()->learndash_helper()->bb_rl_ld_custom_quiz_count( $course_id, $lesson_list, $course_quizzes_list );
$pagination_urls     = bb_load_readylaunch()->learndash_helper()->bb_rl_custom_next_prev_url( $content_urls );
$current_quiz_no     = bb_load_readylaunch()->learndash_helper()->bb_rl_ld_custom_quiz_key( $quiz_urls );

// Find lesson number if quiz is associated with a lesson
$lesson_no = 1;
if ( $lesson_id ) {
	foreach ( $lesson_list as $les ) {
		if ( $les->ID == $lesson_id ) {
			break;
		}
		++$lesson_no;
	}
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--quiz">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-quiz' ); ?>>
			<div class="bb-rl-quiz-block">
				<header class="bb-rl-entry-header">
					<div class="bb-rl-heading">
						<div class="bb-rl-quiz-count">
							<span class="bb-pages">
								<?php echo esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) ); ?> <?php echo esc_html( $current_quiz_no ); ?>
								<span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo esc_html( count( $quiz_urls ) ); ?></span>
							</span>
						</div>
						<div class="bb-rl-quiz-title">
							<h1 class="bb-rl-entry-title"><?php echo esc_html( $quiz_post->post_title ); ?></h1>
						</div>
						<?php if ( $lesson_post ) : ?>
							<div class="bb-rl-lesson-link">
								<span class="bb-rl-lesson-title"><?php esc_html_e( 'Part of:', 'buddyboss' ); ?>
									<a href="<?php echo esc_url( get_permalink( $lesson_post->ID ) ); ?>"><?php echo esc_html( $lesson_post->post_title ); ?></a>
								</span>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( has_post_thumbnail( $quiz_id ) ) : ?>
						<div class="bb-rl-quiz-featured-image">
							<?php echo get_the_post_thumbnail( $quiz_id, 'full' ); ?>
						</div>
					<?php endif; ?>

					<div class="bb-rl-quiz-meta">
						<?php if ( $is_enrolled ) : ?>
							<div class="bb-rl-quiz-status">
								<?php
								$quiz_completed = function_exists( 'learndash_is_quiz_complete' ) ? learndash_is_quiz_complete( $user_id, $quiz_id ) : false;
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
											// translators: %d is the number of attempts.
											printf(
												esc_html__( 'Attempts: %d', 'buddyboss' ),
												esc_html( $attempts_count )
											);
											?>
										</span>
										<?php if ( $attempts_left > 0 ) : ?>
											<span class="bb-rl-attempts-left">
												<?php
												// translators: %d is the number of attempts remaining.
												printf(
													esc_html__( '(%d remaining)', 'buddyboss' ),
													esc_html( $attempts_left )
												);
												?>
											</span>
										<?php elseif ( 0 === $attempts_left ) : ?>
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

				<div class="bb-rl-entry-content">
					<?php
					// Check if quiz content should be shown based on lesson progression
					$show_content = true;
					if ( function_exists( 'learndash_is_quiz_accessable' ) ) {
						$quiz_access  = learndash_is_quiz_accessable( $user_id, $quiz_post, true, $course_id );
						$show_content = ( true === $quiz_access );
					}

					if ( $show_content ) {
						echo wp_kses_post( apply_filters( 'the_content', $quiz_post->post_content ) );

						// Show quiz content/form if available
						if ( function_exists( 'learndash_get_template_part' ) ) {
							/**
							 * Fires before the quiz content starts.
							 *
							 * @since BuddyBoss [BBVERSION]
							 *
							 * @param int $quiz_id   Quiz ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-quiz-before', $quiz_post->ID, $course_id, $user_id );
						}
					} else {
						?>
						<div class="bb-rl-quiz-locked">
							<p><?php esc_html_e( 'This quiz is not yet available. Please complete the previous lessons and topics first.', 'buddyboss' ); ?></p>
						</div>
						<?php
					}
					?>
				</div>
			</div>

			<nav class="bb-rl-quiz-footer">
				<div class="bb-rl-quiz-actions">
					<div class="bb-rl-course-steps">
						<?php
						if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
							$shown_content_key = 'learndash-shortcode-wrap-ld_navigation-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
							if ( false === strstr( $content, $shown_content_key ) ) {
								$shortcode_out = do_shortcode( '[ld_navigation course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
								if ( ! empty( $shortcode_out ) ) {
									echo wp_kses_post( $shortcode_out );
								}
							}
						} else {
							// For quiz, we typically don't show course-steps module as it's handled differently
							// The quiz form itself handles completion and navigation
						}
						?>
					</div>
					<div class="bb-rl-quiz-count">
						<span class="bb-pages">
							<?php echo esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) ); ?> <?php echo esc_html( $current_quiz_no ); ?>
							<span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo esc_html( count( $quiz_urls ) ); ?></span>
						</span>
					</div>
					<div class="learndash_next_prev_link">
						<?php
						if ( isset( $pagination_urls['prev'] ) && '' !== $pagination_urls['prev'] ) {
							echo wp_kses_post( $pagination_urls['prev'] );
						} else {
							echo '<span class="prev-link empty-post"><i class="bb-icons-rl-caret-left"></i>' . esc_html__( 'Previous', 'buddyboss' ) . '</span>';
						}

						$quiz_completed = ( function_exists( 'learndash_is_quiz_complete' ) ? learndash_is_quiz_complete( $user_id, $quiz_post->ID ) : false );
						if (
							isset( $pagination_urls['next'] ) &&
							'' !== $pagination_urls['next'] &&
							$quiz_completed
						) {
							echo $pagination_urls['next'];
						} else {
							echo '<span class="next-link empty-post">' . esc_html__( 'Next', 'buddyboss' ) . '<i class="bb-icons-rl-caret-right"></i></span>';
						}
						?>
					</div>
				</div>
			</nav>
		</article>
	</main>
</div>
