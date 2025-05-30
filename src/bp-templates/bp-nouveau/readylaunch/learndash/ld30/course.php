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
$ld_permalinks = get_option( 'learndash_settings_permalinks', array() );
$course_slug   = isset( $ld_permalinks['courses'] ) ? $ld_permalinks['courses'] : 'courses';

// Get course steps
$course_steps = learndash_get_course_steps( $course_id );
$lessons = learndash_get_course_lessons_list( $course_id );
$lesson_count = array_column( $lessons, 'post' );
$topic_count = array_column( $course_steps, 'post' );

// Essential variables for course content listing (from theme file)
$post      = get_post( $course_id ); // Get the WP_Post object.
$course_model    = \LearnDash\Core\Models\Course::create_from_post( $post );
$content   = $course_model->get_content();

// Get basic course data from the course object.
$courses_options            = learndash_get_option( 'sfwd-courses' );
$lessons_options            = learndash_get_option( 'sfwd-lessons' );
$quizzes_options            = learndash_get_option( 'sfwd-quiz' );
$logged_in                  = is_user_logged_in();
$current_user               = wp_get_current_user();
$has_access                 = sfwd_lms_has_access( $course_id, $user_id );
$materials                  = $course_model->get_materials();
$quizzes                    = learndash_get_course_quiz_list( $course_id, $user_id );
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$has_topics                 = $topic_count > 0;

if ( ! empty( $lessons ) ) {
	foreach ( $lessons as $lesson ) {
		$lesson_topics[ $lesson['post']->ID ] = learndash_topic_dots( $lesson['post']->ID, false, 'array', null, $course_id );
		if ( ! empty( $lesson_topics[ $lesson['post']->ID ] ) ) {
			$has_topics = true;

			$topic_pager_args                     = array(
				'course_id' => $course_id,
				'lesson_id' => $lesson['post']->ID,
			);
			$lesson_topics[ $lesson['post']->ID ] = learndash_process_lesson_topics_pager( $lesson_topics[ $lesson['post']->ID ], $topic_pager_args );
		}
	}
}

// Get course meta and certificate.
$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
if ( ! is_array( $course_meta ) ) {
	$course_meta = array();
}
if ( ! isset( $course_meta['sfwd-courses_course_disable_content_table'] ) ) {
	$course_meta['sfwd-courses_course_disable_content_table'] = false;
}

// Additional variables needed for course content listing
$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons );
global $course_pager_results;
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
			
			<div class="bb-rl-course-content">
				<div class="bb-rl-course-content-inner">
					<?php if ( ! empty( $lessons ) ) : ?>
						<div class="bb-rl-course-content-header">
							<div class="bb-rl-course-content-header-inner">
								<?php
								/**
								 * Fires before the course heading.
								 *
								 * @since 3.0.0
								 *
								 * @param int $course_id Course ID.
								 * @param int $user_id   User ID.
								 */
								do_action( 'learndash-course-heading-before', $course_id, $user_id );
								?>
								<h2>
									<?php
									printf(
									// translators: placeholder: Course.
										esc_html_x( '%s Content', 'placeholder: Course', 'buddyboss' ),
										LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
									);
									?>
								</h2>
								<?php
								/**
								 * Fires after the course heading.
								 *
								 * @since 3.0.0
								 *
								 * @param int $course_id Course ID.
								 * @param int $user_id   User ID.
								 */
								do_action( 'learndash-course-heading-after', $course_id, $user_id );
								?>
								<div class="bb-rl-course-content-meta">
									<div class="bb-rl-course-content-meta-item">
										<span><?php echo esc_html( sizeof( $lesson_count ) ); ?> <?php esc_html_e( 'Lessons', 'buddyboss' ); ?></span>
									</div>
									<div class="bb-rl-course-content-meta-item">
										<span><?php echo esc_html( sizeof( $topic_count ) ); ?> <?php esc_html_e( 'Topics', 'buddyboss' ); ?></span>
									</div>
								</div>
							</div>
							<div class="ld-item-list-actions" data-ld-expand-list="true">

								<?php
								/**
								 * Fires before the course expand.
								 *
								 * @since 3.0.0
								 *
								 * @param int $course_id Course ID.
								 * @param int $user_id   User ID.
								 */
								do_action( 'learndash-course-expand-before', $course_id, $user_id );

								$lesson_container_ids = implode(
									' ',
									array_filter(
										array_map(
											function ( $lesson_id ) use ( $user_id, $course_id ) {
												$topics  = learndash_get_topic_list( $lesson_id, $course_id );
												$quizzes = learndash_get_lesson_quiz_list( $lesson_id, $user_id, $course_id );

												// Ensure we only include this ID if there is something to collapse/expand.
												if (
													empty( $topics )
													&& empty( $quizzes )
												) {
													return '';
												}

												return "ld-expand-{$lesson_id}-container";
											},
											array_keys( $lesson_topics )
										)
									)
								);
								?>

								<?php
								// Only display if there is something to expand.
								if ( $has_topics || $has_lesson_quizzes ) :
									?>
									<button
											aria-controls="<?php echo esc_attr( $lesson_container_ids ); ?>"
											class="ld-expand-button ld-primary-background"
											id="<?php echo esc_attr( 'ld-expand-button-' . $course_id ); ?>"
											data-ld-expands="<?php echo esc_attr( $lesson_container_ids ); ?>"
											data-ld-expand-text="<?php echo esc_attr__( 'Expand All Section', 'buddyboss' ); ?>"
											data-ld-collapse-text="<?php echo esc_attr__( 'Collapse All Sections', 'buddyboss' ); ?>"
									>
										<span class="ld-icon-arrow-down ld-icon"></span>
										<span class="ld-text"><?php echo esc_attr__( 'Expand All Sections', 'buddyboss' ); ?></span>
									</button> <!--/.ld-expand-button-->
								<?php

								/**
								 * Filters whether to expand all course steps by default. Default is false.
								 *
								 * @since 2.5.0
								 *
								 * @param boolean $expand_all Whether to expand all course steps.
								 * @param int     $course_id  Course ID.
								 * @param string  $context    The context where course is expanded.
								 */
								if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_lessons_listing_main' ) ) :
								?>
									<script>
										jQuery( function () {
											setTimeout( function () {
												jQuery( "<?php echo esc_attr( '#ld-expand-button-' . $course_id ); ?>" ).trigger( 'click' );
											}, 1000 );
										} );
									</script>
								<?php
								endif;

								endif;

								/**
								 * Fires after the course content expand button.
								 *
								 * @since 3.0.0
								 *
								 * @param int $course_id Course ID.
								 * @param int $user_id   User ID.
								 */
								do_action( 'learndash-course-expand-after', $course_id, $user_id );
								?>

							</div> <!--/.ld-item-list-actions-->
						</div><!-- .bb-rl-course-content-header -->
					<?php endif;

					/**
					 * Identify if we should show the course content listing
					 *
					 * @var $show_course_content [bool]
					 */
					$show_course_content = ( ! $has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

					if ( $show_course_content ) : ?>

						<div class="ld-item-list ld-lesson-list bb-rl-ld-lesson-list">

							<?php
							/**
							 * Fires before the course content listing
							 *
							 * @since 3.0.0
							 *
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-course-content-list-before', $course_id, $user_id );

							/**
							 * Content listing
							 *
							 * @since 3.0.0
							 *
							 * ('listing.php');
							 */
							learndash_get_template_part(
								'course/listing.php',
								array(
									'course_id'                  => $course_id,
									'user_id'                    => $user_id,
									'lessons'                    => $lessons,
									'lesson_topics'              => $lesson_topics,
									'quizzes'                    => $quizzes,
									'has_access'                 => $has_access,
									'course_pager_results'       => $course_pager_results,
									'lesson_progression_enabled' => $lesson_progression_enabled,
								),
								true
							);

							/**
							 * Fires before the course content listing.
							 *
							 * @since 3.0.0
							 *
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-course-content-list-after', $course_id, $user_id );
							?>

						</div> <!--/.ld-item-list-->

					<?php endif; ?>

					<div class="bb-rl-entry-content">
						<h2><?php esc_html_e( 'About course', 'buddyboss' ); ?></h2>
						<?php the_content(); ?>
					</div>
					
					<?php if ( ! $is_enrolled ) : ?>
						<div class="bb-rl-course-join">
							<?php
							echo learndash_payment_buttons( $course );
							?>
						</div>
					<?php endif; ?>
				</div> <!-- /.bb-rl-course-content-inner -->
				<div class="bb-rl-course-content-sidebar bb-rl-widget-sidebar ">
					<div class="widget">
						<h2 class="widget-title">
							<?php esc_html_e( 'Recently enrolled', 'buddyboss' ); ?>
						</h2>
						<div class="widget-content">
							<?php
							// Get recently enrolled members for the current course
							if ( function_exists( 'learndash_get_users_for_course' ) ) {
								$course_id = get_the_ID();
								$enrolled_users_query = learndash_get_users_for_course( $course_id, array( 'number' => 10 ), false );
								
								// Get the actual user IDs from the WP_User_Query object
								$enrolled_users = array();
								if ( $enrolled_users_query instanceof WP_User_Query && ! empty( $enrolled_users_query->get_results() ) ) {
									$enrolled_users = $enrolled_users_query->get_results();
								}
								
								if ( ! empty( $enrolled_users ) ) {
									// Sort by enrollment date (most recent first)
									$user_enrollments = array();
									foreach ( $enrolled_users as $user_id ) {
										$enrolled_date = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
										if ( empty( $enrolled_date ) ) {
											$enrolled_date = time(); // Fallback to current time if no enrollment date
										}
										$user_enrollments[] = array(
											'user_id' => $user_id,
											'enrolled_date' => $enrolled_date
										);
									}
									
									// Sort by enrollment date (newest first)
									usort( $user_enrollments, function( $a, $b ) {
										return $b['enrolled_date'] - $a['enrolled_date'];
									});
									
									// Limit to 5 most recent enrollments
									$recent_enrollments = array_slice( $user_enrollments, 0, 10 );
									
									if ( ! empty( $recent_enrollments ) ) {
										echo '<div class="bb-rl-recent-enrolled-members">';
										
										foreach ( $recent_enrollments as $enrollment ) {
											$user_id = $enrollment['user_id'];
											$user_data = get_userdata( $user_id );
											
											if ( $user_data ) {
												$user_link = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : get_author_posts_url( $user_id );
												$display_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $user_id ) : $user_data->display_name;
												?>
												<div class="bb-rl-enrolled-member-item">
													<a href="<?php echo esc_url( $user_link ); ?>" title="<?php echo esc_attr( $display_name ); ?>" data-balloon-pos="up" data-balloon="<?php echo esc_attr( $display_name ); ?>">
														<?php 
														// Use bp_core_fetch_avatar with proper parameters
														if ( function_exists( 'bp_core_fetch_avatar' ) ) {
															echo wp_kses_post( bp_core_fetch_avatar(
																array(
																	'item_id' => $user_id,
																	'width' => 48,
																	'height' => 48,
																	'type' => 'full',
																	'html' => true,
																)
															) );
														}
														if ( function_exists( 'bb_user_presence_html' ) ) {
															bb_user_presence_html( $user_id );
														}
														?>
													</a>
												</div>
												<?php
											}
										}
										
										echo '</div>';
									}
								} else {
									echo '<p>' . esc_html__( 'No members enrolled yet.', 'buddyboss' ) . '</p>';
								}
							}
							?>
						</div>
					</div>
				</div><!-- /.bb-rl-course-content-sidebar -->
			</div> <!-- /.bb-rl-course-content -->
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