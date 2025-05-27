<?php
/**
 * LearnDash Single Course Template for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current course ID
$course_id = get_the_ID();
$user_id = get_current_user_id();

// Get course progress
$course_progress = learndash_course_progress(
	array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);

// Get the ReadyLaunch instance to check if sidebar is enabled
$readylaunch = BB_Readylaunch::instance();

// Course data
$course = get_post( $course_id );
$course_settings = learndash_get_setting( $course_id );
$course_price = learndash_get_course_price( $course_id );
$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );
$course_status = learndash_course_status( $course_id, $user_id );

// Get course steps
$course_steps = learndash_get_course_steps( $course_id );
$lessons = learndash_get_course_lessons_list( $course_id );
$lesson_count = array_column( $lessons, 'post' );
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class('bb-rl-learndash-course'); ?>>
			<header class="bb-rl-entry-header">
				<div class="bb-rl-course-banner flex">
					<div class="bb-rl-course-overview">
						<?php
							if ( taxonomy_exists( 'ld_course_category' ) ) {
								// category.
								$course_cats = get_the_terms( $course->ID, 'ld_course_category' );
								if ( ! empty( $course_cats ) ) {
									?>
									<div class="bb-rl-course-category">
										<?php foreach ( $course_cats as $course_cat ) { ?>
											<span class="bb-rl-course-category-item">
												<a title="<?php echo $course_cat->name; ?>" href="<?php printf( '%s/%s/?search=&filter-categories=%s', home_url(), $course_slug, $course_cat->slug ); ?>">
													<?php echo $course_cat->name; ?>
												</a>
												<span>,</span>
											</span>
										<?php } ?>
									</div>
									<?php
								}
							}
						?>
						<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
						<div class="bb-rl-course-meta">
							<div class="bb-rl-meta-item">
								<div class="bb-rl-author-name">
									<?php echo '<span class="bb-rl-author-name-label">' . esc_html__( 'By', 'buddyboss' ) . '</span> ' . get_the_author_meta( 'first_name', $course->post_author ); ?>
								</div>
							</div>
							<div class="bb-rl-meta-item bb-rl-course-enrolled-date">
								<?php echo esc_html__( 'You enrolled this course on ', 'buddyboss' ); ?> <strong>20 June, 2024</strong> <!-- TODO: Add dynamic enrolled date -->
							</div>
							<?php if ( $is_enrolled ) : ?>
								<div class="bb-rl-course-status">
									<?php if ( ! empty( $course_progress ) ) : ?>
										<div class="bb-rl-course-progress">
											<span class="bb-rl-percentage"><span class="bb-rl-percentage-figure"><?php echo (int) $course_progress['percentage']; ?>%</span> <?php esc_html_e( 'Completed', 'buddyboss' ); ?></span>
											<div class="bb-rl-progress-bar">
												<div class="bb-rl-progress" style="width: <?php echo (int) $course_progress['percentage']; ?>%"></div>
											</div>
											
										</div>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<div class="bb-rl-course-price">
									<?php if ( ! empty( $course_price['type'] ) && 'open' === $course_price['type'] ) : ?>
										<span class="bb-rl-price bb-rl-free"><?php esc_html_e( 'Free', 'buddyboss' ); ?></span>
									<?php elseif ( ! empty( $course_price['type'] ) && 'paynow' === $course_price['type'] ) : ?>
										<span class="bb-rl-price"><?php echo esc_html( $course_price['price'] ); ?></span>
									<?php elseif ( ! empty( $course_price['type'] ) && 'subscribe' === $course_price['type'] ) : ?>
										<span class="bb-rl-price"><?php echo sprintf( esc_html__( 'Subscription: %s', 'buddyboss' ), esc_html( $course_price['price'] ) ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="bb-rl-course-figure">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="bb-rl-course-featured-image">
								<?php the_post_thumbnail( 'full' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="bb-rl-course-details">
					<div class="bb-rl-course-details-item">
						<i class="bb-icons-rl-book-open-text"></i>
						<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Lesson', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php echo esc_html( sizeof( $lesson_count ) ); ?>
							</div>
						</div>
					</div>

					<div class="bb-rl-course-details-item">
						<i class="bb-icons-rl-student"></i>
						<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Enrolled', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php echo esc_html( sizeof( $lesson_count ) ); ?>
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
								<?php echo get_the_modified_date(); ?>
							</div>
						</div>
					</div>
				</div>
			</header>

			<div class="bb-rl-entry-content">
				<?php the_content(); ?>
			</div>
			
			<?php if ( ! empty( $lessons ) ) : ?>
				<div class="bb-rl-course-content">
					<h3><?php esc_html_e( 'Course Content', 'buddyboss' ); ?></h3>
					
					<div class="bb-rl-lessons-list">
						<?php foreach ( $lessons as $lesson ) : ?>
							<div class="bb-rl-lesson-item">
								<a href="<?php echo esc_url( get_permalink( $lesson['post']->ID ) ); ?>" class="bb-rl-lesson-link">
									<div class="bb-rl-lesson-title">
										<?php echo esc_html( $lesson['post']->post_title ); ?>
									</div>
									
									<?php if ( $is_enrolled ) : ?>
										<div class="bb-rl-lesson-status">
											<?php if ( isset( $lesson['status'] ) && 'completed' === $lesson['status'] ) : ?>
												<span class="bb-rl-completed"><?php esc_html_e( 'Completed', 'buddyboss' ); ?></span>
											<?php else : ?>
												<span class="bb-rl-incomplete"><?php esc_html_e( 'Not completed', 'buddyboss' ); ?></span>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( ! $is_enrolled ) : ?>
				<div class="bb-rl-course-join">
					<?php
					echo learndash_payment_buttons( $course );
					?>
				</div>
			<?php endif; ?>
		</article>
	</main>

	<?php if ( $readylaunch->bb_is_sidebar_enabled_for_courses() ) : ?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div> 