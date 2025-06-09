<?php
/**
 * LearnDash Single Topic Template for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure LearnDash functions are available
if ( ! class_exists( 'SFWD_LMS' ) || ! function_exists( 'learndash_get_course_id' ) ) {
	// Fallback to default content if LearnDash functions aren't available
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

$lesson_list     = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lesson_list     = array_column( $lesson_list, 'post' );
$content_urls    = bb_load_readylaunch()->learndash_helper()->bb_rl_ld_custom_pagination( $course_id, $lesson_list );
$pagination_urls = bb_load_readylaunch()->learndash_helper()->bb_rl_custom_next_prev_url( $content_urls );

// Find lesson number
$lesson_no = 1;
foreach ( $lesson_list as $les ) {
	if ( $les->ID == $lesson_id ) {
		break;
	}
	++$lesson_no;
}

// Find topic number within the lesson
$topic_no = 1;
foreach ( $topics as $topic_item ) {
	if ( $topic_item->ID == $post->ID ) {
		break;
	}
	++$topic_no;
}

// Define variables for course-steps module compatibility
$logged_in                = is_user_logged_in();
$course_settings          = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $course_id ) : array();
$all_quizzes_completed    = true; // For topics, we'll assume quizzes are handled separately
$previous_topic_completed = true;
if ( function_exists( 'learndash_is_topic_accessable' ) ) {
	$previous_topic_completed = learndash_is_topic_accessable( $user_id, $post );
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--topic">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-topic' ); ?>>
			<div class="bb-rl-topic-block">
				<header class="bb-rl-entry-header">
					<div class="bb-rl-heading">
						<div class="bb-rl-topic-count">
							<span class="bb-pages">
								<?php echo LearnDash_Custom_Label::get_label( 'lesson' ); ?> <?php echo $lesson_no; ?>,
								<?php echo LearnDash_Custom_Label::get_label( 'topic' ); ?> <?php echo $topic_no; ?>
								<span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo count( $topics ); ?></span>
							</span>
						</div>
						<div class="bb-rl-topic-title">
							<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
						</div>
						<?php if ( $lesson_post ) : ?>
							<div class="bb-rl-lesson-link">
								<span class="bb-rl-lesson-title"><?php esc_html_e( 'Part of:', 'buddyboss' ); ?>
									<a href="<?php echo esc_url( get_permalink( $lesson_post->ID ) ); ?>"><?php echo esc_html( $lesson_post->post_title ); ?></a>
								</span>
							</div>
						<?php endif; ?>
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

				<div class="bb-rl-entry-content">
					<?php the_content(); ?>
				</div>

				<?php if ( ! empty( $quizzes ) ) : ?>
					<div class="bb-rl-topic-quizzes">
						<h3><?php esc_html_e( 'Topic Quizzes', 'buddyboss' ); ?></h3>
						<ul class="bb-rl-quizzes-list">
							<?php foreach ( $quizzes as $quiz ) : ?>
								<li class="bb-rl-quiz-item">
									<a href="<?php echo esc_url( get_permalink( $quiz['post']->ID ) ); ?>" class="bb-rl-quiz-link">
										<?php echo esc_html( $quiz['post']->post_title ); ?>
										<?php if ( isset( $quiz['status'] ) && $quiz['status'] == 'completed' ) : ?>
											<span class="bb-rl-quiz-completed"><?php esc_html_e( 'Completed', 'buddyboss' ); ?></span>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>

			<nav class="bb-rl-topic-footer">
				<div class="bb-rl-topic-actions">
					<div class="bb-rl-course-steps">
						<?php
						if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
							$shown_content_key = 'learndash-shortcode-wrap-ld_navigation-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
							if ( false === strstr( $content, $shown_content_key ) ) {
								$shortcode_out = do_shortcode( '[ld_navigation course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
								if ( ! empty( $shortcode_out ) ) {
									echo $shortcode_out;
								}
							}
						} else {

							/**
							 * Set a variable to switch the next button to complete button
							 *
							 * @var $can_complete [bool] - can the user complete this or not?
							 */
							$can_complete = false;
							if ( $all_quizzes_completed && $logged_in && ! empty( $course_id ) ) :
								$can_complete = $previous_topic_completed;

								/**
								 * Filters whether a user can complete the topic or not.
								 *
								 * @since BuddyBoss [BBVERSION]
								 *
								 * @param boolean $can_complete Whether user can complete topic or not.
								 * @param int     $post_id      Topic ID.
								 * @param int     $course_id    Course ID.
								 * @param int     $user_id      User ID.
								 */
								$can_complete = apply_filters( 'learndash-topic-can-complete', true, get_the_ID(), $course_id, $user_id );
							endif;
							learndash_get_template_part(
								'modules/course-steps.php',
								array(
									'course_id'        => $course_id,
									'course_step_post' => $post,
									'user_id'          => $user_id,
									'course_settings'  => isset( $course_settings ) ? $course_settings : array(),
									'can_complete'     => $can_complete,
									'context'          => 'topic',
								),
								true
							);
						}
						?>
					</div>
					<div class="bb-rl-topic-count">
						<span class="bb-pages">
							<?php echo LearnDash_Custom_Label::get_label( 'lesson' ); ?> <?php echo $lesson_no; ?>,
							<?php echo LearnDash_Custom_Label::get_label( 'topic' ); ?> <?php echo $topic_no; ?>
							<span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo count( $topics ); ?></span>
						</span>
					</div>
					<div class="learndash_next_prev_link">
						<?php
						if ( isset( $pagination_urls['prev'] ) && $pagination_urls['prev'] != '' ) {
							echo $pagination_urls['prev'];
						} else {
							echo '<span class="prev-link empty-post"><i class="bb-icons-rl-caret-left"></i>' . esc_html__( 'Previous', 'buddyboss' ) . '</span>';
						}
						?>
						<?php
						if (
							(
								isset( $pagination_urls['next'] ) &&
								apply_filters( 'learndash_show_next_link', learndash_is_topic_complete( $user_id, $post->ID ), $user_id, $post->ID ) &&
								$pagination_urls['next'] != ''
							) ||
							(
								isset( $pagination_urls['next'] ) &&
								$pagination_urls['next'] != '' &&
								isset( $course_settings['course_disable_lesson_progression'] ) &&
								$course_settings['course_disable_lesson_progression'] === 'on'
							)
						) {
							echo $pagination_urls['next'];
						} else {
							echo '<span class="next-link empty-post">' . esc_html__( 'Next Topic', 'buddyboss' ) . '<i class="bb-icons-rl-caret-right"></i></span>';
						}
						?>
					</div>
				</div>
			</nav>
		</article>
	</main>
</div>
