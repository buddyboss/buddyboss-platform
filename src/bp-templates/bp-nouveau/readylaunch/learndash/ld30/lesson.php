<?php
/**
 * LearnDash Single Lesson Template for ReadyLaunch
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
			<article id="post-<?php the_ID(); ?>" <?php post_class('bb-rl-learndash-lesson'); ?>>
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

$lesson_id = get_the_ID();
$user_id = get_current_user_id();
$course_id = function_exists( 'learndash_get_course_id' ) ? learndash_get_course_id( $lesson_id ) : 0;
$lesson = get_post( $lesson_id );
$lesson_progress = function_exists( 'learndash_lesson_progress' ) ? learndash_lesson_progress( array( 'lesson_id' => $lesson_id, 'user_id' => $user_id, 'array' => true ) ) : array();
$is_enrolled = function_exists( 'sfwd_lms_has_access' ) ? sfwd_lms_has_access( $course_id, $user_id ) : false;
$lesson_status = function_exists( 'learndash_lesson_status' ) ? learndash_lesson_status( $lesson_id, $user_id ) : '';
$topics = function_exists( 'learndash_get_topic_list' ) ? learndash_get_topic_list( $lesson_id, $user_id ) : array();
$prev_lesson = function_exists( 'learndash_get_previous_lesson' ) ? learndash_get_previous_lesson( $lesson_id ) : null;
$next_lesson = function_exists( 'learndash_get_next_lesson' ) ? learndash_get_next_lesson( $lesson_id ) : null;
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class('bb-rl-learndash-lesson'); ?>>
			<header class="bb-rl-entry-header">
				<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="bb-rl-lesson-featured-image">
						<?php the_post_thumbnail( 'full' ); ?>
					</div>
				<?php endif; ?>

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

			<div class="bb-rl-entry-content">
				<?php the_content(); ?>
			</div>

			<?php if ( ! empty( $topics ) ) : ?>
				<div class="bb-rl-lesson-topics">
					<h3><?php esc_html_e( 'Lesson Topics', 'buddyboss' ); ?></h3>
					<ul class="bb-rl-topics-list">
						<?php foreach ( $topics as $topic ) : ?>
							<li class="bb-rl-topic-item">
								<a href="<?php echo esc_url( get_permalink( $topic->ID ) ); ?>" class="bb-rl-topic-link">
									<?php echo esc_html( $topic->post_title ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<nav class="bb-rl-lesson-navigation">
				<?php if ( $prev_lesson ) : ?>
					<a href="<?php echo esc_url( get_permalink( $prev_lesson->ID ) ); ?>" class="bb-rl-prev-lesson"><?php esc_html_e( 'Previous Lesson', 'buddyboss' ); ?></a>
				<?php endif; ?>
				<?php if ( $next_lesson ) : ?>
					<a href="<?php echo esc_url( get_permalink( $next_lesson->ID ) ); ?>" class="bb-rl-next-lesson"><?php esc_html_e( 'Next Lesson', 'buddyboss' ); ?></a>
				<?php endif; ?>
			</nav>
		</article>
	</main>
</div> 