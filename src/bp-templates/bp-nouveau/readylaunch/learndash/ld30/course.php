<?php
/**
 * LearnDash Single Course Template for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current course ID.
$bb_course_id = get_the_ID();
$bb_user_id   = get_current_user_id();

// Get course progress.
$bb_course_progress = learndash_course_progress(
	array(
		'user_id'   => $bb_user_id,
		'course_id' => $bb_course_id,
		'array'     => true,
	)
);

// Get the ReadyLaunch instance to check if sidebar is enabled.
$bb_readylaunch = BB_Readylaunch::instance();

// Course data.
$bb_course          = get_post( $bb_course_id );
$bb_course_settings = learndash_get_setting( $bb_course_id );
$bb_course_price    = learndash_get_course_price( $bb_course_id );
$bb_is_enrolled     = sfwd_lms_has_access( $bb_course_id, $bb_user_id );
$bb_course_status   = learndash_course_status( $bb_course_id, $bb_user_id );
$bb_ld_permalinks   = get_option( 'learndash_settings_permalinks', array() );
$bb_course_slug     = isset( $bb_ld_permalinks['courses'] ) ? $bb_ld_permalinks['courses'] : 'courses';

// Get course steps.
$bb_course_steps = learndash_get_course_steps( $bb_course_id );
$bb_lessons      = learndash_get_course_lessons_list( $bb_course_id );
$bb_lesson_count = array_column( $bb_lessons, 'post' );
$bb_topics_count = count(
	array_filter(
		$bb_course_steps,
		function ( $step_id ) {
			return get_post_type( $step_id ) === 'sfwd-topic';
		}
	)
);

// Essential variables for course content listing (from theme file).
$bb_post_data    = get_post( $bb_course_id );
$bb_course_model = \LearnDash\Core\Models\Course::create_from_post( $bb_post_data );
$bb_content      = $bb_course_model->get_content();

// Get basic course data from the course object.
$bb_courses_options            = learndash_get_option( 'sfwd-courses' );
$bb_lessons_options            = learndash_get_option( 'sfwd-lessons' );
$bb_quizzes_options            = learndash_get_option( 'sfwd-quiz' );
$bb_logged_in                  = is_user_logged_in();
$bb_current_user_data          = wp_get_current_user();
$bb_has_access                 = sfwd_lms_has_access( $bb_course_id, $bb_user_id );
$bb_materials                  = $bb_course_model->get_materials();
$bb_quizzes                    = learndash_get_course_quiz_list( $bb_course_id, $bb_user_id );
$bb_lesson_progression_enabled = learndash_lesson_progression_enabled( $bb_course_id );
$bb_has_topics                 = $bb_topics_count > 0;
$bb_course_pricing             = learndash_get_course_price( $bb_course_id );

if ( ! empty( $bb_lessons ) ) {
	foreach ( $bb_lessons as $bb_lesson ) {
		$bb_lesson_topics[ $bb_lesson['post']->ID ] = learndash_topic_dots( $bb_lesson['post']->ID, false, 'array', null, $bb_course_id );
		if ( ! empty( $bb_lesson_topics[ $bb_lesson['post']->ID ] ) ) {
			$bb_has_topics = true;

			$topic_pager_args = array(
				'course_id' => $bb_course_id,
				'lesson_id' => $bb_lesson['post']->ID,
			);

			$bb_lesson_topics[ $bb_lesson['post']->ID ] = learndash_process_lesson_topics_pager( $bb_lesson_topics[ $bb_lesson['post']->ID ], $topic_pager_args );
		}
	}
}

// Get the WP_User object for the current user.
$bb_user = get_userdata( $bb_user_id );

// Get course meta and certificate.
$bb_course_meta = get_post_meta( $bb_course_id, '_sfwd-courses', true );
if ( ! is_array( $bb_course_meta ) ) {
	$bb_course_meta = array();
}
if ( ! isset( $bb_course_meta['sfwd-courses_course_disable_content_table'] ) ) {
	$bb_course_meta['sfwd-courses_course_disable_content_table'] = false;
}
$bb_course_certficate_link = $bb_course_model->get_certificate_link( $bb_user );

// Additional variables needed for course content listing.
$bb_has_lesson_quizzes = learndash_30_has_lesson_quizzes( $bb_course_id, $bb_lessons );
global $bb_course_pager_results;

$bb_bb_rl_ld_helper = class_exists( 'BB_Readylaunch_Learndash_Helper' ) ? BB_Readylaunch_Learndash_Helper::instance() : null;
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<article id="post-<?php echo esc_attr( get_the_ID() ); ?>" <?php post_class( 'bb-rl-learndash-course' ); ?>>
			<header class="bb-rl-entry-header">
				<div class="bb-rl-course-banner flex">
					<div class="bb-rl-course-overview">
						<?php
						if ( taxonomy_exists( 'ld_course_category' ) ) {
							// category.
							$bb_course_cats = get_the_terms( $bb_course->ID, 'ld_course_category' );
							if ( ! empty( $bb_course_cats ) ) {
								?>
								<div class="bb-rl-course-category">
									<?php foreach ( $bb_course_cats as $bb_course_cat ) { ?>
										<span class="bb-rl-course-category-item">
												<a title="<?php echo esc_attr( $bb_course_cat->name ); ?>" href="<?php printf( '%s/%s/?search=&filter-categories=%s', esc_url( home_url() ), esc_attr( $bb_course_slug ), esc_attr( $bb_course_cat->slug ) ); ?>">
													<?php echo esc_html( $bb_course_cat->name ); ?>
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
								$bb_average = learndash_course_reviews_get_average_review_score( $bb_course_id );
								if ( ! is_bool( $bb_average ) ) {
									$bb_reviews = get_comments(
										wp_parse_args(
											array(),
											array(
												'post_id' => $bb_course_id,
												'type'    => 'ld_review',
												'status'  => 'approve',
												'fields'  => 'ids',
											)
										)
									);
									if ( ! is_array( $bb_reviews ) ) {
										$bb_reviews = array();
									}
									$bb_reviews      = array_filter(
										$bb_reviews,
										'is_int'
									);
									$bb_review_count = count( $bb_reviews );
									?>
									<div class="bb-rl-course-review">
										<span class="star">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="#FFC107" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
												<path d="M10 15l-5.878 3.09 1.122-6.545L.488 6.91l6.561-.955L10 0l2.951 5.955 6.561.955-4.756 4.635 1.122 6.545z" />
											</svg>
										</span>
										<span class="average"><?php echo esc_html( number_format( $bb_average, 1 ) ); ?></span>
										<span class="count">(<?php echo esc_html( $bb_review_count ); ?>)</span>
									</div>
									<?php
								}
							}
							?>
							<div class="bb-rl-meta-item">
								<div class="bb-rl-author-name">
									<?php echo '<span class="bb-rl-author-name-label">' . esc_html__( 'By', 'buddyboss' ) . '</span> ' . esc_html( get_the_author_meta( 'first_name', $bb_course->post_author ) ); ?>
								</div>
							</div>
						</div>
						<?php
						$bb_currency    = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();
						$bb_price       = $bb_course_price['price'];
						$bb_trial_price = ! empty( $bb_course_price['trial_price'] ) ? $bb_course_price['trial_price'] : 0;
						if ( ! $bb_is_enrolled ) {
							if ( 'free' === $bb_course_price['type'] ) {
								?>
								<div class="bb-rl-course-price">
										<span class="bb-rl-price">
										<?php esc_html_e( 'Free', 'buddyboss' ); ?>
										</span>
								</div>
								<?php
							} elseif ( ! empty( $bb_price ) ) {
								if ( $bb_trial_price ) {
									?>
									<div class="bb-rl-composite-price">
										<div class="bb-rl-premium-price bb-rl-price-module">
											<span class="bb-rl-price">
												<span class="ld-currency"><?php echo wp_kses_post( $bb_currency ); ?></span> 
												<?php echo esc_html( $bb_trial_price ); ?>
											</span>
											<span class="bb-rl-price-meta">
												<?php esc_html_e( 'Trial price for ', 'buddyboss' ); ?>
												<span class="bb-rl-meta-trial">
													<?php echo esc_html( $bb_course_price['trial_interval'] ); ?>
													<?php echo esc_html( $bb_course_price['trial_frequency'] ); ?>
												</span>
											</span>
										</div>
										<span class="bb-rl-separator bb-rl-separator--vertical"></span>
										<div class="bb-rl-full-price bb-rl-price-module">
											<span class="bb-rl-price">
												<span class="ld-currency"><?php echo wp_kses_post( $bb_currency ); ?></span> 
												<?php echo wp_kses_post( $bb_price ); ?>
											</span>
											<span class="bb-rl-price-meta">
												<?php esc_html_e( 'Full price every ', 'buddyboss' ); ?>
												<span class="bb-rl-meta-trial">
													<?php echo esc_html( $bb_course_price['interval'] ); ?>
													<?php echo esc_html( $bb_course_price['frequency'] ); ?>
												</span>
												<?php esc_html_e( 'afterward', 'buddyboss' ); ?>
											</span>
										</div>
									</div>
								<?php } else { ?>
									<div class="bb-rl-course-price">
										<span class="bb-rl-price">
											<span class="ld-currency"><?php echo wp_kses_post( $bb_currency ); ?></span> 
											<?php echo wp_kses_post( $bb_price ); ?>
										</span>
									</div>
								<?php } ?>
								<?php
							}
						}
						?>
						<?php
						if ( 'closed' === $bb_course_pricing['type'] ) {
							?>
							<div class="bb-rl-course-status-label bb-rl-notice--plain bb-rl-notice--error">
								<?php esc_html_e( 'This course is currently closed', 'buddyboss' ); ?>
							</div>
						<?php } ?>

						<div class="bb-rl-course-overview-footer">
							<div class="bb-rl-course-action">
								<?php
								if ( 'closed' === $bb_course_pricing['type'] ) {
									?>
									<a href="#" class="bb-rl-course-action-button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-button--disabled">
										<?php esc_html_e( 'Not Enrolled', 'buddyboss' ); ?>
									</a>
								<?php } elseif ( ! $bb_is_enrolled ) { ?>
									<div class="bb-rl-course-join">
										<?php
										echo wp_kses_post( learndash_payment_buttons( $bb_course ) );
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
								if ( $bb_bb_rl_ld_helper ) {
									$bb_bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
										array(
											'course_id' => $bb_course_id,
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
								if ( is_user_logged_in() && isset( $bb_is_enrolled ) && $bb_is_enrolled ) {
									if (
										(
											'open' === $bb_course_price['type'] &&
											0 === (int) $bb_course_progress['percentage'] ) ||
										(
											'open' !== $bb_course_price['type'] &&
											$bb_is_enrolled &&
											0 === $bb_course_progress['percentage']
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
										learndash_status_bubble( $bb_course_status );
									}
								} elseif ( 'free' === $bb_course_price['type'] ) {
									?>
									<div class="ld-status ld-status-incomplete ld-third-background bb-rl-ld-status">
										<?php esc_html_e( 'Free', 'buddyboss' ); ?>
									</div>
									<?php
								} elseif ( 'closed' === $bb_course_price['type'] ) {
									?>
									<div class="ld-status ld-status-closed ld-third-background">
										<?php esc_html_e( 'Closed Course', 'buddyboss' ); ?>
									</div>
									<?php
								} elseif ( 'open' !== $bb_course_price['type'] ) {
									?>
									<div class="ld-status ld-status-incomplete ld-third-background">
										<?php esc_html_e( 'Not Enrolled', 'buddyboss' ); ?>
									</div>
									<?php
								} elseif ( 'open' === $bb_course_price['type'] ) {
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
								<?php echo esc_html( count( $bb_lesson_count ) ); ?>
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
								$bb_enrolled_users = $bb_bb_rl_ld_helper->bb_rl_ld_get_enrolled_users_data(
									array(
										'course_id' => $bb_course_id,
										'limit'     => 10,
										'count'     => true,
										'data'      => false,
									)
								);
								echo ! empty( $bb_enrolled_users['count'] ) ? esc_html( $bb_enrolled_users['count'] ) : 0;
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
					<?php
					// Display certificate if available.
					if ( ! empty( $bb_course_certficate_link ) ) {
						learndash_get_template_part(
							'modules/alert.php',
							array(
								'type'    => 'success bb-rl-ld-alert-certificate',
								'icon'    => 'certificate',
								'message' => __( 'You\'ve earned a certificate!', 'buddyboss' ),
								'button'  => array(
									'url'    => $bb_course_certficate_link,
									'icon'   => 'download',
									'label'  => __( 'Download Certificate', 'buddyboss' ),
									'target' => '_new',
								),
							),
							true
						);
					}
					?>
					<?php if ( ! empty( $bb_lessons ) ) : ?>
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
								do_action( 'learndash-course-heading-before', $bb_course_id, $bb_user_id );
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
								do_action( 'learndash-course-heading-after', $bb_course_id, $bb_user_id );
								?>
								<div class="bb-rl-course-content-meta">
									<div class="bb-rl-course-content-meta-item">
										<span>
											<?php echo esc_html( $bb_topics_count ); ?>
											<?php esc_html_e( 'Topics', 'buddyboss' ); ?></span>
									</div>
									<div class="bb-rl-course-content-meta-item">
										<span>
											<?php echo esc_html( count( $bb_lesson_count ) ); ?>
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
								do_action( 'learndash-course-expand-before', $bb_course_id, $bb_user_id );

								$bb_lesson_container_ids = implode(
									' ',
									array_filter(
										array_map(
											function ( $bb_lesson_id ) use ( $bb_user_id, $bb_course_id ) {
												$bb_topics  = learndash_get_topic_list( $bb_lesson_id, $bb_course_id );
												$bb_quizzes = learndash_get_lesson_quiz_list( $bb_lesson_id, $bb_user_id, $bb_course_id );

												// Ensure we only include this ID if there is something to collapse/expand.
												if (
													empty( $bb_topics )
													&& empty( $bb_quizzes )
												) {
													return '';
												}

												return "ld-expand-{$bb_lesson_id}-container";
											},
											array_keys( $bb_lesson_topics )
										)
									)
								);
								?>

								<?php
								// Only display if there is something to expand.
								if ( $bb_has_topics || $bb_has_lesson_quizzes ) :
									?>
									<button
											aria-controls="<?php echo esc_attr( $bb_lesson_container_ids ); ?>"
											class="ld-expand-button ld-primary-background"
											id="<?php echo esc_attr( 'ld-expand-button-' . $bb_course_id ); ?>"
											data-ld-expands="<?php echo esc_attr( $bb_lesson_container_ids ); ?>"
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
								if ( apply_filters( 'learndash_course_steps_expand_all', false, $bb_course_id, 'course_lessons_listing_main' ) ) :
								?>
									<script>
										jQuery( function () {
											setTimeout( function () {
												jQuery( "<?php echo esc_attr( '#ld-expand-button-' . $bb_course_id ); ?>" ).trigger( 'click' );
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
								do_action( 'learndash-course-expand-after', $bb_course_id, $bb_user_id );
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
					$show_course_content = ( ! $bb_has_access && 'on' === $bb_course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

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
							do_action( 'learndash-course-content-list-before', $bb_course_id, $bb_user_id );

							learndash_get_template_part(
								'course/listing.php',
								array(
									'course_id'                  => $bb_course_id,
									'user_id'                    => $bb_user_id,
									'lessons'                    => $bb_lessons,
									'lesson_topics'              => $bb_lesson_topics,
									'quizzes'                    => $bb_quizzes,
									'has_access'                 => $bb_has_access,
									'course_pager_results'       => $bb_course_pager_results,
									'lesson_progression_enabled' => $bb_lesson_progression_enabled,
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
							do_action( 'learndash-course-content-list-after', $bb_course_id, $bb_user_id );
							?>

						</div> <!--/.ld-item-list-->

					<?php endif; ?>

					<div class="bb-rl-entry-content">
						<?php if ( get_the_content() ) : ?>
							<h2><?php esc_html_e( 'About course', 'buddyboss' ); ?></h2>
							<?php the_content(); ?>
						<?php endif; ?>
					</div>
				</div> <!-- /.bb-rl-course-content-inner -->
				<div class="bb-rl-course-content-sidebar bb-rl-widget-sidebar ">
					<div class="widget">
						<h2 class="widget-title">
							<?php esc_html_e( 'Recently enrolled', 'buddyboss' ); ?>
						</h2>
						<div class="widget-content">
							<?php
							if ( $bb_bb_rl_ld_helper ) {
								$bb_bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
									array(
										'course_id' => $bb_course_id,
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

	<?php if ( $bb_readylaunch->bb_is_sidebar_enabled_for_courses() ) : ?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div>
