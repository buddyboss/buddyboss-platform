<?php
/**
 * Template for single course page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/single-mpcs-course.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses\helpers;
use memberpress\courses\models as models;

// Start the Loop.
while ( have_posts() ) :
	the_post();
	global $post;

	?>
	<div class="entry entry-content">
		<div class="columns">

			<div id="mpcs-main" class="mpcs-main column col-9 col-md-12">
				

				<?php if ( ! empty( models\Lesson::get_thumbnail( $post ) ) ) : ?>
					<figure class="figure">
						<a href="<?php the_permalink(); ?>" alt="<?php the_title_attribute(); ?>">
							<img src="<?php echo esc_url( models\Lesson::get_thumbnail( $post ) ); ?>" alt="">
						</a>
					</figure>
				<?php endif; ?>

				<div class="course-progress">
					<?php echo helpers\Courses::classroom_sidebar_progress( $post ); ?>
				</div>


				<?php setup_postdata( $post->ID ); ?>
				<?php the_content(); ?>
				<?php
				$options              = \get_option( 'mpcs-options' );
				$show_course_comments = helpers\Options::val( $options, 'show-course-comments' );
				if ( ! empty( $show_course_comments ) && ( comments_open() || get_comments_number() ) ) {
					comments_template();
				}
				?>

				<div class="mepr-rl-footer-widgets">
					<?php if ( is_active_sidebar( 'mpcs_classroom_courses_overview_footer' ) ) : ?>
						<div id="mpcs-courses-overview-footer-widget"
							class="mpcs-courses-overview-footer-widget widget-area" role="complementary">
							<?php dynamic_sidebar( 'mpcs_classroom_courses_overview_footer' ); ?>
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

	<?php
endwhile; // End the loop.
