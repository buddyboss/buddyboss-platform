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
	$current_user_id     = get_current_user_id();
	$user_has_progress   = ! empty( $course_participants ) && in_array( $current_user_id, $course_participants );

	// Check user access once and reuse.
	$has_membership_access = false;
	if ( is_user_logged_in() && ! empty( $memberships ) ) {
		$mepr_user             = new \MeprUser( $current_user_id );
		$has_membership_access = ! \MeprRule::is_locked_for_user( $mepr_user, $post );
	}

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
					<h1 class="bb-rl-entry-title"><?php echo esc_html( $course->post_title ); ?></h1>

					<div class="bb-rl-course-meta">
						<div class="bb-rl-meta-item">
							<?php
							// Get course author full name.
							$first_name             = get_the_author_meta( 'first_name', $post->post_author );
							$last_name              = get_the_author_meta( 'last_name', $post->post_author );
							$course_author_fullname = trim( $first_name . ' ' . $last_name );
							
							// Fallback to display_name if first/last name are empty.
							if ( empty( $course_author_fullname ) ) {
								$course_author_fullname = get_the_author_meta( 'display_name', $post->post_author );
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
					// Get course price.
					if ( ! empty( $memberships ) ) {
						$membership = $memberships[0];
						if ( isset( $membership->price ) && floatval( $membership->price ) <= 0 ) {
							$course_price = __( 'Free', 'buddyboss' );
						} else {
							$course_price = \MeprAppHelper::format_currency( $membership->price );
							// Add period type if it's recurring.
							if ( ! empty( $membership->period_type ) && 'lifetime' !== $membership->period_type ) {
								$course_price .= '/' . esc_html( $membership->period_type );
							}
						}
					}

					if ( ! empty( $course_price ) ) {
						?>
						<div class="bb-rl-course-price">
							<?php echo esc_html( $course_price ); ?>
						</div>
						<?php
					}

					// Add membership purchase button near price.
					if ( ! empty( $memberships ) && ( ! is_user_logged_in() || ! $has_membership_access ) ) {
						$primary_membership = $memberships[0];
						?>
						<div class="bb-rl-course-purchase-button">
							<a href="<?php echo esc_url( get_permalink( $primary_membership->ID ) ); ?>" 
								class="button">
								<?php esc_html_e( 'Get Access', 'buddyboss' ); ?>
							</a>
						</div>
						<?php
					}

					// Add continue/bookmark button for enrolled users.
					if ( is_user_logged_in() && ( empty( $memberships ) || $has_membership_access ) ) {
						// Get next lesson URL for bookmarking.
						$next_lesson = models\UserProgress::next_lesson( $current_user_id, $post->ID );
						if ( $next_lesson && is_object( $next_lesson ) ) {
							$bookmark_url = get_permalink( $next_lesson->ID );
							$button_text  = $user_has_progress ? __( 'Continue', 'buddyboss' ) : __( 'Start', 'buddyboss' );
							?>
							<div class="bb-rl-course-continue-button">
								<a href="<?php echo esc_url( $bookmark_url ); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
									<span><?php echo esc_html( $button_text ); ?></span>
									<i class="bb-icons-rl-caret-right"></i>
								</a>
							</div>
							<?php
						}
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

				<div class="bb-rl-course-details-item">
					<i class="bb-icons-rl-wave-triangle"></i>
					<div>
						<div class="bb-rl-course-details-label"><?php echo esc_html__( 'Status', 'buddyboss' ); ?></div>
												<div class="bb-rl-course-details-value">
							<?php 
							if ( is_user_logged_in() ) {
								// Simple logic: Enrolled if no membership required OR user has membership access
								if ( empty( $memberships ) || $has_membership_access ) {
									?>
									<div class="bb-rl-course-enrollment-status bb-rl-status-enrolled">
										<?php esc_html_e( 'Enrolled', 'buddyboss' ); ?>
									</div>
									<?php
								} else {
									?>
									<div class="bb-rl-course-enrollment-status bb-rl-status-idle">
										<?php esc_html_e( 'Not enrolled', 'buddyboss' ); ?>
									</div>
									<?php
								}
							} else {
								// Not logged in
								?>
								<div class="bb-rl-course-enrollment-status bb-rl-status-idle">
									<?php esc_html_e( 'Not enrolled', 'buddyboss' ); ?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>

				<div class="bb-rl-course-details-item">
					<i class="bb-icons-rl-book-open-text"></i>
					<div>
						<div class="bb-rl-course-details-label"><?php echo esc_html__( 'Lessons', 'buddyboss' ); ?></div>
						<div class="bb-rl-course-details-value">
							<?php
							// Get course lesson count.
							$course_lesson_count = $course->number_of_lessons();
							echo esc_html( $course_lesson_count . ' ' . _n( 'lesson', 'lessons', $course_lesson_count, 'buddyboss' ) );
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
							// Get course enrollment count.
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
							<?php
							// Get course content last modified date.
							$course_modified_date = get_post_modified_time( 'U', false, $post->ID );
							$lessons = $course->lessons();
							$latest_modified_date = $course_modified_date;

							// Check all lessons for the most recent modification date.
							foreach ( $lessons as $lesson ) {
								$lesson_modified_date = get_post_modified_time( 'U', false, $lesson->ID );
								if ( $lesson_modified_date > $latest_modified_date ) {
									$latest_modified_date = $lesson_modified_date;
								}
							}

							echo esc_html( date_i18n( get_option( 'date_format' ), $latest_modified_date ) );
							?>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div class="bb-rl-course-content">
			<div class="bb-rl-course-content-inner">
				<div class="bb-rl-course-content-panel">
					<div class="bb-rl-course-content-module">
						<?php
						setup_postdata( $post->ID );
						the_content();
						?>
					</div>
					<div class="bb-rl-course-comment-module bb-rl-lms-content-comments">
						<?php
						$options              = \get_option( 'mpcs-options' );
						$show_course_comments = helpers\Options::val( $options, 'show-course-comments' );
						if ( ! empty( $show_course_comments ) && ( comments_open() || get_comments_number() ) ) {
							comments_template();
						}
						?>
					</div>
				</div>
			</div>
			<div class="bb-rl-course-content-sidebar bb-rl-widget-sidebar">
				<?php
				ob_start();
				?>
				<div class="widget">
					<div class="widget-content">
						<?php
						if ( helpers\Lessons::is_a_lesson( $post ) ) {
							do_action( 'mpcs_classroom_lesson_sidebar_menu', $course, $post );
							if ( $course->has_resources() ) {
								?>
								<div class="mpcs-sidebar-resources">
									<a class="tile mepr-resources" href="<?php echo esc_url( get_permalink( $course->ID ) . '?action=resources' ); ?>" target="_blank">
										<div class="tile-icon">
											<i class="mpcs-print"></i>
										</div>
										<div class="tile-content">
											<p class="tile-title m-0"><?php esc_html_e( 'Resources', 'buddyboss' ); ?></p>
										</div>
									</a>
								</div>
								<?php
							}
							echo helpers\Courses::display_course_overview( false, true );
						}

						if ( helpers\Courses::is_a_course( $post ) ) {
							?>
							<div class="section mpcs-sidebar-menu bb-rl-widget-sidebar-menu">
								<?php
								do_action( 'mpcs_classroom_sidebar_menu', $course, $post );
								if ( $course->has_resources() ) {
									?>
									<a class="tile <?php \MeprAccountHelper::active_nav( 'resources', 'is-active' ); ?>" href="<?php echo esc_url( get_permalink() . '?action=resources' ); ?>">
										<div class="tile-icon">
											<i class="bb-icons-rl-printer"></i>
										</div>
										<div class="tile-content">
											<p class="tile-title m-0"><?php esc_html_e( 'Resources', 'buddyboss' ); ?></p>
										</div>
									</a>
									<?php
								}

								if ( $course->user_progress( $current_user_id ) >= 100 && $course->certificates_enable == 'enabled' ) {
									$cert_url = admin_url( 'admin-ajax.php?action=mpcs-course-certificate' );
									$cert_url = add_query_arg(
										array(
											'user'   => $current_user_id,
											'course' => $post->ID,
										),
										$cert_url
									);
									$share_link = add_query_arg(
										array(
											'shareable' => 'true',
										),
										$cert_url
									);
									?>
									<a target="_blank" class="tile <?php \MeprAccountHelper::active_nav( 'certificate', 'is-active' ); ?>" href="<?php echo esc_url_raw( $cert_url ); ?>">
										<div class="tile-icon">
											<i class="mpcs-award"></i>
										</div>
										<div class="tile-content">
											<p class="tile-title m-0">
												<?php esc_html_e( 'Certificate', 'buddyboss' ); ?>
												<?php if ( $course->certificates_share_link == 'enabled' ) { ?>
													<i title="<?php esc_attr_e( 'Copied Shareable Certificate Link', 'buddyboss' ); ?>" class="mpcs-share" data-clipboard-text="<?php echo esc_url( $share_link ); ?>" onclick="return false;"></i>
												<?php } ?>
											</p>
										</div>
									</a>
								<?php
								}
								$remove_instructor_link = helpers\Options::val($options, 'remove-instructor-link');
								if ( empty( $remove_instructor_link ) ) { ?>
									<a class="tile <?php \MeprAccountHelper::active_nav( 'instructor', 'is-active' ); ?>" href="<?php echo esc_url( get_permalink() . '?action=instructor' ); ?>">
										<div class="tile-icon">
											<i class="bb-icons-rl-user"></i>
										</div>
										<div class="tile-content">
											<p class="tile-title m-0"><?php esc_html_e( 'Your Instructor', 'buddyboss' ); ?></p>
										</div>
									</a>
								<?php } ?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				$widget_content = ob_get_clean();
				if ( ! empty( $widget_content ) ) {
					echo $widget_content;
				}
				?>
			</div>
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
