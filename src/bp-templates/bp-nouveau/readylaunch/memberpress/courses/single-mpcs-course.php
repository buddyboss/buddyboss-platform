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

	$course_price        = '';
	$course              = new models\Course( $post->ID );
	$memberships         = $course->memberships();
	$course_participants = models\UserProgress::find_all_course_participants( $post->ID );

	?>
	<div class="bb-rl-memprlms-course bb-rl-lms-course">			

		<div class="bb-rl-entry-header">
			<div class="bb-rl-course-banner flex">
				<div class="bb-rl-course-overview">
					<?php
					$course_category_names = bb_rl_mpcs_get_course_category_names( $post->ID );
					if ( ! empty( $course_category_names ) ) {
						?>
						<div class="bb-rl-course-category">
							<div class="bb-rl-course-category-item">
								<?php echo esc_html( $course_category_names ); ?>
							</div>
						</div>
						<?php
					}
					?>
					<h1 class="bb-rl-entry-title"><?php echo $course->post_title ?></h1>

					<div class="bb-rl-course-meta">
						<div class="bb-rl-meta-item">
							<?php
							// get course author full name.
							$first_name = get_the_author_meta('first_name', $post->post_author);
							$last_name = get_the_author_meta('last_name', $post->post_author);
							$course_author_fullname = trim($first_name . ' ' . $last_name);
							
							// Fallback to display_name if first/last name are empty
							if (empty($course_author_fullname)) {
								$course_author_fullname = get_the_author_meta('display_name', $post->post_author);
							}
							
							if ( ! empty( $course_author_fullname ) ) {
								?>
								<div class="bb-rl-author-name">
									<span class="bb-rl-author-name-label"><?php echo esc_html__( 'By ', 'buddyboss' ); ?></span><?php echo esc_html( trim( $course_author_fullname ) ); ?>
								</div>
								<?php
							}
							?>
						</div>
					</div>

					<?php
					// get course price.
					if ( ! empty( $memberships ) ) {
						$membership = $memberships[0];
						if ( isset( $membership->price ) && floatval( $membership->price ) <= 0 ) {
							$course_price = __( 'Free', 'memberpress-courses' );
						} else {
							$course_price = \MeprAppHelper::format_currency( $membership->price );
							// Add period type if it's recurring.
							if ( ! empty( $membership->period_type ) && 'lifetime' !== $membership->period_type ) {
								$course_price .= '/' . esc_html( $membership->period_type );
							}
						}
					} else { ?>
						<span class="bb-course-type bb-course-type-free bb-rl-course-type-status">
							<?php echo esc_html__( 'Free', 'memberpress-courses' ); ?>
						</span>
					<?php } ?>

					<?php
					if ( ! empty( $course_price ) ) {
						?>
						<div class="bb-rl-course-price">
							<?php echo esc_html( $course_price ); ?>
						</div>
						<?php
					}
					?>

					<div class="bb-rl-course-overview-footer">
						<div class="mpcs-sidebar-wrapper">
							<div class="course-progress">
								<?php echo helpers\Courses::classroom_sidebar_progress( $post ); ?>
							</div>
						</div>
						<div class="bb-rl-course-enrolled"></div>
					</div>
				</div>
				<div class="bb-rl-course-figure">
					<div class="bb-rl-course-featured-image">
						<?php if ( ! empty( models\Lesson::get_thumbnail( $post ) ) ) : ?>
							<a href="<?php the_permalink(); ?>" alt="<?php the_title_attribute(); ?>">
								<img src="<?php echo esc_url( models\Lesson::get_thumbnail( $post ) ); ?>" alt="">
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="bb-rl-course-details">

				<?php if ( is_user_logged_in() ) { ?>
				<div class="bb-rl-course-details-item">
					<i class="bb-icons-rl-wave-triangle"></i>
					<div>
						<div class="bb-rl-course-details-label"><?php echo esc_html__( 'Status', 'buddyboss' ); ?></div>
						<div class="bb-rl-course-details-value">
							<?php
							if ( ! empty( $course_participants ) && in_array( get_current_user_id(), $course_participants ) ) {
								?>
								<div class="bb-rl-course-enrollment-status bb-rl-status-enrolled">
									<?php esc_html_e( 'Enrolled', 'buddyboss-theme' ); ?>
								</div>
								<?php
							} else {
								?>
								<div class="bb-rl-course-enrollment-status bb-rl-status-idle">
									<?php esc_html_e( 'Not enrolled', 'buddyboss-theme' ); ?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
				<?php } ?>

				<div class="bb-rl-course-details-item">
					<i class="bb-icons-rl-book-open-text"></i>
					<div>
						<div class="bb-rl-course-details-label"><?php echo esc_html__( 'Lessons', 'buddyboss' ); ?></div>
						<div class="bb-rl-course-details-value">
							<?php
							// get course lesson count.
							$course_lesson_count = $course->number_of_lessons();
							echo esc_html( $course_lesson_count . ' ' .  'lessons' );
							?>
						</div>
					</div>
				</div>

				<div class="bb-rl-course-details-item">
					<i class="bb-icons-rl-student"></i>
					<div>
						<div class="bb-rl-course-details-label"><?php echo esc_html__( 'Enrolled', 'buddyboss' ); ?></div>
						<div class="bb-rl-course-details-value">
							<?php
							// get course enrollment count.
							$course_enrollment_count = ! empty( $course_participants ) ? count( $course_participants ) : 0;
							echo esc_html( $course_enrollment_count );
							?>
						</div>
					</div>
				</div>

				<div class="bb-rl-course-details-item">
					<i class="bb-icons-rl-arrows-clockwise"></i>
					<div>
						<div class="bb-rl-course-details-label">
							<?php esc_html_e( 'Update', 'buddyboss' ); ?>
						</div>
						<div class="bb-rl-course-details-value">
							<?php echo esc_html( get_the_modified_date() ); ?>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div class="bb-rl-course-content">
			<div class="bb-rl-course-content-inner">
				<?php setup_postdata( $post->ID ); ?>
				<?php the_content(); ?>
				<?php
				$options              = \get_option( 'mpcs-options' );
				$show_course_comments = helpers\Options::val( $options, 'show-course-comments' );
				if ( ! empty( $show_course_comments ) && ( comments_open() || get_comments_number() ) ) {
					comments_template();
				}
				?>
			</div>
			<div class="bb-rl-course-content-sidebar bb-rl-widget-sidebar"></div>
		</div>

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

	<?php
endwhile; // End the loop.
