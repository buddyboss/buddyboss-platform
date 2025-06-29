<?php
/**
 * Template for single quiz page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/quizzes/single-mpcs-quiz.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\quizzes\models;
use memberpress\quizzes\helpers;
use memberpress\courses;
global $post;

$quiz           = new models\Quiz( $post->ID );
$quiz_available = $quiz->is_available();
?>
<div class="bb-rl-mbprlms-inner-wrapper">
	<div class="bb-rl-quiz-block bb-rl-lms-inner-block">
		<div id="mpcs-main" class="mpcs-main column mpcs-inner-page-main">
			<?php setup_postdata( $post->ID ); ?>
			<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_header' ) ) : ?>
				<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
					<?php dynamic_sidebar( 'mpcs_classroom_lesson_header' ); ?>
				</div>
			<?php endif; ?>

			<?php
			if ( 'enabled' === $quiz->course()->lesson_title ) {
				printf( '<h1 class="bb-rl-entry-title">%s</h1>', esc_html( get_the_title() ) );
			}
			?>

			<?php
			if ( $quiz_available ) {
				?>
				<div class="mpcs-main-content"><?php the_content(); ?></div>
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
</div>
