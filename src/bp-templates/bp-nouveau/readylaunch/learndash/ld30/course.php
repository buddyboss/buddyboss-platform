<?php
/**
 * LearnDash Single Course Template for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current course ID.
$course_id = get_the_ID();
$user_id   = get_current_user_id();

// Get course progress.
$course_progress = learndash_course_progress(
	array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);

// Get the ReadyLaunch instance to check if sidebar is enabled.
$readylaunch = BB_Readylaunch::instance();

// Course data.
$course          = get_post( $course_id );
$course_settings = learndash_get_setting( $course_id );
$course_price    = learndash_get_course_price( $course_id );
$is_enrolled     = sfwd_lms_has_access( $course_id, $user_id );
$course_status   = learndash_course_status( $course_id, $user_id );
$ld_permalinks   = get_option( 'learndash_settings_permalinks', array() );
$course_slug     = isset( $ld_permalinks['courses'] ) ? $ld_permalinks['courses'] : 'courses';

// Get course steps.
$course_steps = learndash_get_course_steps( $course_id );
$lessons      = learndash_get_course_lessons_list( $course_id );
$lesson_count = array_column( $lessons, 'post' );
$topics_count = count(
	array_filter(
		$course_steps,
		function ( $step_id ) {
			return get_post_type( $step_id ) === 'sfwd-topic';
		}
	)
);

// Essential variables for course content listing (from theme file).
$post_data    = get_post( $course_id );
$course_model = \LearnDash\Core\Models\Course::create_from_post( $post_data );
$content      = $course_model->get_content();

// Get basic course data from the course object.
$courses_options            = learndash_get_option( 'sfwd-courses' );
$lessons_options            = learndash_get_option( 'sfwd-lessons' );
$quizzes_options            = learndash_get_option( 'sfwd-quiz' );
$logged_in                  = is_user_logged_in();
$current_user_data          = wp_get_current_user();
$has_access                 = sfwd_lms_has_access( $course_id, $user_id );
$materials                  = $course_model->get_materials();
$quizzes                    = learndash_get_course_quiz_list( $course_id, $user_id );
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$has_topics                 = $topics_count > 0;
$course_pricing             = learndash_get_course_price( $course_id );

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

// Additional variables needed for course content listing.
$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons );
global $course_pager_results;

$bb_rl_ld_helper = class_exists( 'BB_Readylaunch_Learndash_Helper' ) ? BB_Readylaunch_Learndash_Helper::instance() : null;
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-course' ); ?>>
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
												<a title="<?php echo esc_attr( $course_cat->name ); ?>" href="<?php printf( '%s/%s/?search=&filter-categories=%s', esc_url( home_url() ), esc_attr( $course_slug ), esc_attr( $course_cat->slug ) ); ?>">
													<?php echo esc_html( $course_cat->name ); ?>
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
							<?php
							if ( class_exists( 'LearnDash_Course_Reviews_Loader' ) ) {
								$average = learndash_course_reviews_get_average_review_score( $course_id );
								if ( ! is_bool( $average ) ) {
									$reviews = get_comments(
										wp_parse_args(
											array(),
											array(
												'post_id' => $course_id,
												'type'    => 'ld_review',
												'status'  => 'approve',
												'fields'  => 'ids',
											)
										)
									);
									if ( ! is_array( $reviews ) ) {
										$reviews = array();
									}
									$reviews      = array_filter(
										$reviews,
										'is_int'
									);
									$review_count = count( $reviews );
									?>
									<div class="bb-rl-course-review">
										<span class="star">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="#FFC107" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
												<path d="M10 15l-5.878 3.09 1.122-6.545L.488 6.91l6.561-.955L10 0l2.951 5.955 6.561.955-4.756 4.635 1.122 6.545z" />
											</svg>
										</span>
										<span class="average"><?php echo esc_html( number_format( $average, 1 ) ); ?></span>
										<span class="count">(<?php echo esc_html( $review_count ); ?>)</span>
									</div>
									<?php
								}
							}
							?>
							<div class="bb-rl-meta-item">
								<div class="bb-rl-author-name">
									<?php echo '<span class="bb-rl-author-name-label">' . esc_html__( 'By', 'buddyboss' ) . '</span> ' . esc_html( get_the_author_meta( 'first_name', $course->post_author ) ); ?>
								</div>
							</div>
						</div>
						<?php
						$currency    = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();
						$price       = $course_price['price'];
						$trial_price = ! empty( $course_price['trial_price'] ) ? $course_price['trial_price'] : 0;
						if ( ! $is_enrolled ) {
							if ( 'free' === $course_price['type'] ) {
								?>
									<div class="bb-rl-course-price">
										<span class="bb-rl-price">
										<?php esc_html_e( 'Free', 'buddyboss' ); ?>
										</span>
									</div>
									<?php
							} elseif ( ! empty( $price ) ) {
								if ( $trial_price ) {
									?>
									<div class="bb-rl-composite-price">
										<div class="bb-rl-premium-price bb-rl-price-module">
											<span class="bb-rl-price">
												<span class="ld-currency"><?php echo wp_kses_post( $currency ); ?></span> 
												<?php echo $trial_price; ?>
											</span>
											<span class="bb-rl-price-meta">
												<?php esc_html_e( 'Trial price for ', 'buddyboss' ); ?>
												<span class="bb-rl-meta-trial">
													<?php echo $course_price['trial_interval']; ?>
													<?php echo $course_price['trial_frequency']; ?>
												</span>
											</span>
										</div>
										<div class="bb-rl-full-price bb-rl-price-module">
											<span class="bb-rl-price">
												<span class="ld-currency"><?php echo wp_kses_post( $currency ); ?></span> 
												<?php echo wp_kses_post( $price ); ?>
											</span>
											<span class="bb-rl-price-meta">
												<?php esc_html_e( 'Full price every ', 'buddyboss' ); ?>
												<span class="bb-rl-meta-trial">
													<?php echo $course_price['interval']; ?>
													<?php echo $course_price['frequency']; ?>
												</span>
												<?php esc_html_e( 'afterward', 'buddyboss' ); ?>
											</span>
										</div>
									</div>
								<?php } else { ?>
									<div class="bb-rl-course-price">
										<span class="bb-rl-price">
											<span class="ld-currency"><?php echo wp_kses_post( $currency ); ?></span> 
											<?php echo wp_kses_post( $price ); ?>
										</span>
									</div>
								<?php } ?>		
								<?php
							}
						}
						?>
						<?php
						if ( 'closed' === $course_pricing['type'] ) {
							?>
							<div class="bb-rl-course-status-label bb-rl-notice--plain bb-rl-notice--error">
								<?php esc_html_e( 'This course is currently closed', 'buddyboss' ); ?>
							</div>
						<?php } ?>

						<div class="bb-rl-course-overview-footer">
							<div class="bb-rl-course-action">
								<?php
								if ( 'closed' === $course_pricing['type'] ) {
									?>
									<a href="#" class="bb-rl-course-action-button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-button--disabled">
										<?php esc_html_e( 'Not Enrolled', 'buddyboss' ); ?>
									</a>
								<?php } elseif ( ! $is_enrolled ) { ?>
									<div class="bb-rl-course-join">
										<?php
										echo learndash_payment_buttons( $course );
										?>
									</div>
								<?php } else { ?>
									<a href="#" class="bb-rl-course-action-button bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
										<?php esc_html_e( 'Take this course', 'buddyboss' ); ?>
									</a>
								<?php } ?>
							</div>
							<div class="bb-rl-course-enrolled">
								<?php
								if ( $bb_rl_ld_helper ) {
									$enrolled_users = $bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
										array(
											'course_id' => $course_id,
											'limit'     => 3,
											'action'    => 'header',
											'count'     => true,
											'data'      => true,
										)
									);
								}
								?>
							</div>
						</div>
					</div>
					<div class="bb-rl-course-figure">
						<?php if ( has_post_thumbnail() ) { ?>
							<div class="bb-rl-course-featured-image">
								<?php the_post_thumbnail( 'full' ); ?>
							</div>
						<?php } else { ?>											
							<div class="bb-rl-course-featured-image">
								<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Course placeholder image', 'buddyboss' ); ?>">
							</div>
						<?php } ?>
					</div>
				</div>

				<div class="bb-rl-course-details">
					<div class="bb-rl-course-details-item">
						<i class="bb-icons-rl-wave-triangle"></i>
						<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Status', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php
								if ( is_user_logged_in() && isset( $is_enrolled ) && $is_enrolled ) {
									if (
										(
											'open' === $course_price['type'] &&
											0 === (int) $course_progress['percentage'] ) ||
										(
											'open' !== $course_price['type'] &&
											$is_enrolled &&
											0 === $course_progress['percentage']
										)
									) {
										?>
										<div class="ld-status ld-status-progress ld-primary-background bb-rl-ld-status">
											<?php
											printf(
											// translators: %s is the course label.
												esc_html__( 'Start %s', 'buddyboss' ),
												esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
											);
											?>
										</div>
										<?php
									} else {
										learndash_status_bubble( $course_status );
									}
								} elseif ( 'free' === $course_price['type'] ) {
									?>
									<div class="ld-status ld-status-incomplete ld-third-background bb-rl-ld-status">
										<?php esc_html_e( 'Free', 'buddyboss' ); ?>
									</div>
									<?php
								} elseif ( 'closed' === $course_price['type'] ) {
									?>
									<div class="ld-status ld-status-closed ld-third-background">
										<?php esc_html_e( 'Closed Course', 'buddyboss' ); ?>
									</div>
									<?php
								} elseif ( 'open' !== $course_price['type'] ) {
									?>
									<div class="ld-status ld-status-incomplete ld-third-background">
										<?php esc_html_e( 'Not Enrolled', 'buddyboss' ); ?>
									</div>
									<?php
								} elseif ( 'open' === $course_price['type'] ) {
									?>
									<div class="ld-status ld-status-progress ld-primary-background bb-rl-ld-status">
										<?php
										printf(
										// translators: %s is the course label.
											esc_html__( 'Start %s', 'buddyboss' ),
											esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
										);
										?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>

					<div class="bb-rl-course-details-item">
						<i class="bb-icons-rl-timer"></i>
						<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Duration', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php esc_html_e( '10 hours', 'buddyboss' ); ?>
							</div>
						</div>
					</div>

					<div class="bb-rl-course-details-item">
						<i class="bb-icons-rl-book-open-text"></i>
						<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Lesson', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php echo esc_html( count( $lesson_count ) ); ?>
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
								<?php
								$enrolled_users = $bb_rl_ld_helper->bb_rl_filter_courses_query(
									array(
										'course_id' => $course_id,
										'limit'     => 10,
										'count'     => true,
										'data'      => false,
									)
								);
								echo ! empty( $enrolled_users['count'] ) ? esc_html( $enrolled_users['count'] ) : 0;
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
								 * @since BuddyBoss [BBVERSION]
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
								 * @since BuddyBoss [BBVERSION]
								 *
								 * @param int $course_id Course ID.
								 * @param int $user_id   User ID.
								 */
								do_action( 'learndash-course-heading-after', $course_id, $user_id );
								?>
								<div class="bb-rl-course-content-meta">
									<div class="bb-rl-course-content-meta-item">
										<span>
											<?php echo esc_html( $topics_count ); ?>
											<?php esc_html_e( 'Topics', 'buddyboss' ); ?></span>
									</div>
									<div class="bb-rl-course-content-meta-item">
										<span>
											<?php echo esc_html( count( $lesson_count ) ); ?>
											<?php esc_html_e( 'Lessons', 'buddyboss' ); ?>
										</span>
									</div>
								</div>
							</div>
							<div class="ld-item-list-actions" data-ld-expand-list="true">

								<?php
								/**
								 * Fires before the course expand.
								 *
								 * @since BuddyBoss [BBVERSION]
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
										<span class="ld-text"><?php echo esc_attr__( 'Expand All Sections', 'buddyboss' ); ?></span>
									</button> <!--/.ld-expand-button-->
									<?php

									/**
									 * Filters whether to expand all course steps by default. Default is false.
									 *
									 * @since BuddyBoss [BBVERSION]
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
								 * @since BuddyBoss [BBVERSION]
								 *
								 * @param int $course_id Course ID.
								 * @param int $user_id   User ID.
								 */
								do_action( 'learndash-course-expand-after', $course_id, $user_id );
								?>

							</div> <!--/.ld-item-list-actions-->
						</div><!-- .bb-rl-course-content-header -->
						<?php
					endif;

					/**
					 * Identify if we should show the course content listing
					 *
					 * @var $show_course_content [bool]
					 */
					$show_course_content = ( ! $has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

					if ( $show_course_content ) :
						?>

						<div class="ld-item-list ld-lesson-list bb-rl-ld-lesson-list">

							<?php
							/**
							 * Fires before the course content listing
							 *
							 * @since BuddyBoss [BBVERSION]
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
									'course_id'            => $course_id,
									'user_id'              => $user_id,
									'lessons'              => $lessons,
									'lesson_topics'        => $lesson_topics,
									'quizzes'              => $quizzes,
									'has_access'           => $has_access,
									'course_pager_results' => $course_pager_results,
									'lesson_progression_enabled' => $lesson_progression_enabled,
								),
								true
							);

							/**
							 * Fires before the course content listing.
							 *
							 * @since BuddyBoss [BBVERSION]
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
				</div> <!-- /.bb-rl-course-content-inner -->
				<div class="bb-rl-course-content-sidebar bb-rl-widget-sidebar ">
					<div class="widget">
						<h2 class="widget-title">
							<?php esc_html_e( 'Recently enrolled', 'buddyboss' ); ?>
						</h2>
						<div class="widget-content">
							<?php
							if ( $bb_rl_ld_helper ) {
								$enrolled_users = $bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
									array(
										'course_id' => $course_id,
										'limit'     => 10,
										'action'    => 'sidebar',
										'count'     => false,
										'data'      => true,
									)
								);
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