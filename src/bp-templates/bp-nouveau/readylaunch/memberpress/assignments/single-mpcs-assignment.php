<?php
/**
 * Template for single assignment page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/assignments/single-mpcs-assignment.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses;

// Start the Loop.
while ( have_posts() ) :
	the_post();
	global $post;

	$lesson           = new courses\models\Lesson( $post->ID );
	$lesson_available = $lesson->is_available();
	?>
	<div class="entry entry-content">
		<div class="columns col-gapless" style="flex-grow: 1;">
			<div id="mpcs-sidebar" class="column col-3 col-md-12 pl-0">

				<?php
				echo courses\helpers\Courses::get_classroom_sidebar( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

			</div>
			<div id="mpcs-main" class="mpcs-main column col-9 col-md-12 mpcs-inner-page-main">
				<?php setup_postdata( $post->ID ); ?>
				<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_header' ) ) : ?>
					<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
						<?php dynamic_sidebar( 'mpcs_classroom_lesson_header' ); ?>
					</div>
				<?php endif; ?>

				<?php

				if ( 'enabled' === $lesson->course()->lesson_title ) {
					printf( '<h1 class="entry-title">%s</h1>', esc_html( get_the_title() ) );
				}
				?>

				<?php
				if ( $lesson_available ) {
					?>
					<div class="mpcs-main-content"><?php the_content(); ?></div>
					<?php
				} else {
					$button_class = 'btn btn-green is-purple';
					require courses\VIEWS_PATH . '/lessons/lesson_locked.php';
				}
				?>

				<?php if ( is_active_sidebar( 'mpcs_classroom_lesson_footer' ) ) : ?>
					<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
						<?php dynamic_sidebar( 'mpcs_classroom_lesson_footer' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php
	wp_reset_postdata();
endwhile; // End the loop.
