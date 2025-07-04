<?php
/**
 * Template for single lesson page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/single-mpcs-lesson.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses\models;
use memberpress\courses\helpers;
global $post;

$lesson           = new models\Lesson( $post->ID );
$lesson_available = $lesson->is_available();
?>
<div class="bb-rl-mbprlms-inner-wrapper">
	<div class="bb-rl-lesson-block bb-rl-lms-inner-block">
		<?php setup_postdata( $post->ID ); ?>
		<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_header' ) ) : ?>
			<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
				<?php dynamic_sidebar( 'mpcs_classroom_lesson_header' ); ?>
			</div>
		<?php endif; ?>

		<?php
		if ( 'enabled' === $lesson->course()->lesson_title ) {
			/* translators: %s: lesson title */
			printf( '<h1 class="bb-rl-entry-title">%s</h1>', esc_html( get_the_title() ) );
		}
		?>

		<?php
		if ( $lesson_available ) {
			?>
			<div class="mpcs-main-content"><?php the_content(); ?></div>
			<div class="bb-rl-course-content-comments">
				<?php
				$options              = get_option( 'mpcs-options' );
				$show_course_comments = helpers\Options::val( $options, 'show-course-comments' );
				if ( ! empty( $show_course_comments ) && ( comments_open() || get_comments_number() ) ) {
					comments_template();
				}
				?>
			</div>
			<?php
		} else {
			$button_class = 'btn btn-green is-purple';
			require MeprView::file( '/lessons/lesson_locked' );
		}
		?>

		<div class="mepr-rl-footer-widgets">
			<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_footer' ) ) : ?>
				<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
					<?php dynamic_sidebar( 'mpcs_classroom_lesson_footer' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( is_active_sidebar( 'mepr_rl_global_footer' ) ) : ?>
				<div id="mepr-rl-global-footer-widget" class="mepr-rl-global-footer-widget widget-area"
					role="complementary">
					<?php dynamic_sidebar( 'mepr_rl_global_footer' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
