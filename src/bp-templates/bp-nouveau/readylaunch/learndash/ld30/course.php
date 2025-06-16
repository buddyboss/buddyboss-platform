<?php
/**
 * LearnDash Single Course Template for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 *
 * Available Variables:
 *
 * $course_id                   : (int) ID of the course
 * $course                      : (object) Post object of the course
 * $course_settings             : (array) Settings specific to current course
 *
 * $courses_options             : Options/Settings as configured on Course Options page
 * $lessons_options             : Options/Settings as configured on Lessons Options page
 * $quizzes_options             : Options/Settings as configured on Quiz Options page
 *
 * $user_id                     : Current User ID
 * $logged_in                   : User is logged in
 * $current_user                : (object) Currently logged in user object
 *
 * $course_status               : Course Status
 * $has_access                  : User has access to course or is enrolled.
 * $materials                   : Course Materials
 * $has_course_content          : Course has course content
 * $lessons                     : Lessons Array
 * $quizzes                     : Quizzes Array
 * $lesson_progression_enabled  : (true/false)
 * $has_topics                  : (true/false)
 * $lesson_topics               : (array) lessons topics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bb_post = get_post( $course_id ); // Get the WP_Post object.
if ( LearnDash_Theme_Register::get_active_theme_instance()->supports_views( LDLMS_Post_Types::get_post_type_key( learndash_get_post_type_slug( 'course' ) ) ) ) {
	$course  = \LearnDash\Core\Models\Course::create_from_post( $bb_post );
	$content = $course->get_content();

	// Get basic course data from the course object.
	$course_product             = $course->get_product();
	$course_settings            = $course_product->get_pricing_settings();
	$courses_options            = learndash_get_option( 'sfwd-courses' );
	$lessons_options            = learndash_get_option( 'sfwd-lessons' );
	$quizzes_options            = learndash_get_option( 'sfwd-quiz' );
	$user_id                    = get_current_user_id();
	$logged_in                  = is_user_logged_in();
	$course_status              = learndash_course_status( $course_id, $user_id );
	$has_access                 = $course_product->user_has_access();
	$materials                  = $course->get_materials();
	$has_course_content         = $course->has_steps();
	$lessons                    = learndash_get_course_lessons_list( $course_id );
	$quizzes                    = $course->get_quizzes();
	$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
	$has_topics                 = $course->get_topics_number() > 0;

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
	$course_certficate_link = $course->get_certificate_link( $user_id );
} else {
	$materials              = ( isset( $materials ) ) ? $materials : '';
	$lessons                = ( isset( $lessons ) ) ? $lessons : array();
	$quizzes                = ( isset( $quizzes ) ) ? $quizzes : array();
	$lesson_topics          = ( isset( $lesson_topics ) ) ? $lesson_topics : array();
	$course_certficate_link = ( isset( $course_certficate_link ) ) ? $course_certficate_link : '';
}

// Get course progress.
$bb_course_progress = learndash_course_progress(
	array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);

// Get the ReadyLaunch instance to check if sidebar is enabled.
$bb_readylaunch = BB_Readylaunch::instance();

// Course data.
$bb_course_price  = learndash_get_course_price( $course_id );
$bb_is_enrolled   = sfwd_lms_has_access( $course_id, $user_id );
$bb_course_status = learndash_course_status( $course_id, $user_id );
$bb_ld_permalinks = get_option( 'learndash_settings_permalinks', array() );
$bb_course_slug   = isset( $bb_ld_permalinks['courses'] ) ? $bb_ld_permalinks['courses'] : 'courses';

// Get course steps.
$bb_course_steps = learndash_get_course_steps( $course_id );
$bb_lesson_count = array_column( $lessons, 'post' );
$bb_topics_count = count(
	array_filter(
		$bb_course_steps,
		function ( $step_id ) {
			return get_post_type( $step_id ) === 'sfwd-topic';
		}
	)
);

// Get basic course data from the course object.
$bb_course_pricing = learndash_get_course_price( $course_id );

if ( ! empty( $lessons ) ) {
	foreach ( $lessons as $bb_lesson ) {
		$bb_lesson_topics[ $bb_lesson['post']->ID ] = learndash_topic_dots( $bb_lesson['post']->ID, false, 'array', null, $course_id );
		if ( ! empty( $bb_lesson_topics[ $bb_lesson['post']->ID ] ) ) {
			$has_topics = true;

			$topic_pager_args = array(
				'course_id' => $course_id,
				'lesson_id' => $bb_lesson['post']->ID,
			);

			$bb_lesson_topics[ $bb_lesson['post']->ID ] = learndash_process_lesson_topics_pager( $bb_lesson_topics[ $bb_lesson['post']->ID ], $topic_pager_args );
		}
	}
}

// Get course meta and certificate.
$bb_course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
if ( ! is_array( $bb_course_meta ) ) {
	$bb_course_meta = array();
}
if ( ! isset( $bb_course_meta['sfwd-courses_course_disable_content_table'] ) ) {
	$bb_course_meta['sfwd-courses_course_disable_content_table'] = false;
}

$is_completed = learndash_course_completed( $user_id, $course_id );

// Additional variables needed for course content listing.
$bb_has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons );
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
							$bb_course_cats = get_the_terms( $course->ID, 'ld_course_category' );
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
								$bb_average = learndash_course_reviews_get_average_review_score( $course_id );
								if ( ! is_bool( $bb_average ) ) {
									$bb_reviews = get_comments(
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
									<?php echo '<span class="bb-rl-author-name-label">' . esc_html__( 'By', 'buddyboss' ) . '</span> ' . esc_html( get_the_author_meta( 'first_name', $course->post_author ) ); ?>
								</div>
							</div>
						</div>
						<?php
						$bb_currency    = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();
						$bb_price       = $bb_course_price['price'];
						$bb_trial_price = ! empty( $bb_course_price['trial_price'] ) ? $bb_course_price['trial_price'] : 0;

						if ( 'open' === $bb_course_price['type'] || 'free' === $bb_course_price['type'] ) {
							if ( 'open' === $bb_course_price['type'] ) {
								echo '<span class="bb-course-type bb-course-type-open">' . __( 'Open Registration', 'buddyboss' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo '<span class="bb-course-type bb-course-type-free">' . __( 'Free', 'buddyboss' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} elseif ( 'closed' === $bb_course_price['type'] ) {
							$learndash_payment_buttons = learndash_payment_buttons( $course );
							if ( empty( $learndash_payment_buttons ) ) {
								if ( false === $bb_is_enrolled ) {
									echo '<span class="ld-status ld-status-incomplete ld-third-background ld-text">' . esc_html__( 'This course is currently closed', 'buddyboss' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									if ( ! empty( $bb_course_price['price'] ) ) {
										echo '<span class="bb-course-type bb-course-type-paynow">' . wp_kses_post( learndash_get_price_formatted( $bb_course_price['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								}
							} elseif ( ! empty( $bb_course_price['price'] ) ) {
								echo '<span class="bb-course-type bb-course-type-paynow">' . wp_kses_post( learndash_get_price_formatted( $bb_course_price['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} elseif ( 'paynow' === $bb_course_price['type'] || 'subscribe' === $bb_course_price['type'] ) {
							if ( false === $bb_is_enrolled ) {
								if ( 'paynow' === $bb_course_price['type'] ) {
									if ( ! empty( $bb_course_price['price'] ) ) {
										echo '<span class="bb-course-type bb-course-type-paynow">' . wp_kses_post( learndash_get_price_formatted( $bb_course_price['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								} else {
									$bb_course_price_billing_p3 = get_post_meta( $course_id, 'course_price_billing_p3', true );
									$bb_course_price_billing_t3 = get_post_meta( $course_id, 'course_price_billing_t3', true );

									if ( 'D' === $bb_course_price_billing_t3 ) {
										$bb_course_price_billing_t3 = 'day(s)';
									} elseif ( 'W' === $bb_course_price_billing_t3 ) {
										$bb_course_price_billing_t3 = 'week(s)';
									} elseif ( 'M' === $bb_course_price_billing_t3 ) {
										$bb_course_price_billing_t3 = 'month(s)';
									} elseif ( 'Y' === $bb_course_price_billing_t3 ) {
										$bb_course_price_billing_t3 = 'year(s)';
									}

									$recurring = ( '' === $bb_course_price_billing_p3 ) ? 0 : $bb_course_price_billing_p3;

									$recurring_label = '<span class="bb-course-type bb-course-type-subscribe">';
									if ( '' === $bb_course_price['price'] && 'subscribe' === $bb_course_price['type'] ) {
										$recurring_label .= '<span class="bb-course-type bb-course-type-subscribe">' . __( 'Free', 'buddyboss' ) . '</span>';
									} else {
										$recurring_label .= wp_kses_post( learndash_get_price_formatted( $bb_course_price['price'] ) );
									}
									$recurring_label .= '<span class="course-bill-cycle"> / ' . $recurring . ' ' . $course_price_billing_t3 . '</span></span>';

									echo $recurring_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							}
						}
						?>

						<div class="bb-rl-course-overview-footer">
							<div class="bb-rl-course-action">
								<?php
								if ( empty( $bb_course_progress ) && 100 > $bb_course_progress ) {
									$btn_advance_class = 'btn-advance-start';
									$btn_advance_label = sprintf(
										/* translators: %s: Course label. */
										__( 'Start %s', 'buddyboss' ),
										LearnDash_Custom_Label::get_label( 'course' )
									);
								} elseif ( 100 === (int) $bb_course_progress ) {
									$btn_advance_class = 'btn-advance-completed';
									$btn_advance_label = __( 'Completed', 'buddyboss' );
								} else {
									$btn_advance_class = 'btn-advance-continue';
									$btn_advance_label = __( 'Continue', 'buddyboss' );
								}

								if ( 0 === learndash_get_course_steps_count( $course_id ) && false !== $is_enrolled ) {
									$btn_advance_class .= ' btn-advance-disable';
								}

								// Determine button class and text based on conditions.
								$button_class  = 'learndash_join_button';
								$button_class .= ! empty( $btn_advance_class ) ? ' ' . $btn_advance_class : '';

								$button_html = '';
								if ( 'open' === $bb_course_pricing['type'] || 'free' === $bb_course_pricing['type'] ) {
									if ( $learndash_login_modal ) {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $login_url ),
											__( 'Login to Enroll', 'buddyboss' )
										);
									} elseif ( 'free' === $bb_course_pricing['type'] && false === $bb_is_enrolled ) {
										$button_html = learndash_payment_buttons( $course );
									} else {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $resume_link ),
											esc_html( $btn_advance_label )
										);
									}
								} elseif ( 'closed' === $bb_course_pricing['type'] ) {
									$learndash_payment_buttons = learndash_payment_buttons( $course );
									if ( empty( $learndash_payment_buttons ) && false !== $bb_is_enrolled ) {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $resume_link ),
											esc_html( $btn_advance_label )
										);
									} else {
										$button_html = $learndash_payment_buttons;
									}
								} elseif ( 'paynow' === $bb_course_pricing['type'] || 'subscribe' === $bb_course_pricing['type'] ) {
									if ( false === $bb_is_enrolled ) {
										$meta                       = get_post_meta( $course_id, '_sfwd-courses', true );
										$bb_course_pricing['type']  = $meta['sfwd-courses_course_price_type'];
										$bb_course_pricing['price'] = $meta['sfwd-courses_course_price'];

										if ( 'subscribe' === $bb_course_pricing['type'] && '' === $bb_course_pricing['price'] ) {
											$button_text = ! empty( $custom_button_label )
												? esc_attr( $custom_button_label )
												: LearnDash_Custom_Label::get_label( 'button_take_this_course' );

											$button_html = sprintf(
												'<form method="post">
													<input type="hidden" value="%d" name="course_id" />
													<input type="hidden" name="course_join" value="%s" />
													<input type="submit" value="%s" class="btn-join" id="btn-join" />
												</form>',
												$course->ID,
												wp_create_nonce( 'course_join_' . get_current_user_id() . '_' . $course->ID ),
												$button_text
											);
										} else {
											$button_html = learndash_payment_buttons( $course );
										}
									} else {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $resume_link ),
											esc_html( $btn_advance_label )
										);
									}
								}

								// Output the button HTML if we have content.
								if ( ! empty( $button_html ) ) {
									printf( '<div class="%s">%s</div>', esc_attr( $button_class ), $button_html );
								}
								?>
							</div>
							<div class="bb-rl-course-enrolled">
								<?php
								if ( $bb_bb_rl_ld_helper ) {
									$bb_bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
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
					<?php
					$status_html = '';
					$ld_product  = null;

					if ( class_exists( 'LearnDash\Core\Models\Product' ) && isset( $course_id ) ) {
						$ld_product = LearnDash\Core\Models\Product::find( (int) $course_id );
					}

					if ( $ld_product ) {
						if ( ! $has_access ) {
							$ld_seats_available      = $ld_product->get_seats_available();
							$ld_seats_available_text = ! empty( $ld_seats_available )
								? sprintf(
									// translators: placeholder: number of places remaining.
									_nx(
										'(%s place remaining)',
										'(%s places remaining)',
										$ld_seats_available,
										'placeholder: number of places remaining',
										'buddyboss'
									),
									number_format_i18n( $ld_seats_available )
								)
								: '';

							if ( $ld_product->has_ended() ) {
								$tooltips = sprintf(
									// translators: placeholder: course.
									esc_attr_x( 'This %s has ended', 'placeholder: course', 'buddyboss' ),
									esc_html( learndash_get_custom_label_lower( 'course' ) )
								);
								$status_html = sprintf(
									'<div class="ld-status ld-status-incomplete ld-third-background" data-ld-tooltip="%s">%s</div>',
									$tooltips,
									__( 'Not Enrolled', 'buddyboss' )
								);
							} elseif ( ! $ld_product->has_started() ) {
								$tooltips = ! $ld_product->can_be_purchased()
									? sprintf(
										// translators: placeholder: course, course start date.
										esc_attr_x( 'This %1$s starts on %2$s', 'placeholder: course, course start date', 'buddyboss' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) ),
										esc_html( learndash_adjust_date_time_display( $ld_product->get_start_date() ) )
									)
									: sprintf(
										// translators: placeholder: course, course start date.
										esc_attr_x( 'It is a pre-order. Enroll in this %1$s to get access after %2$s', 'placeholder: course', 'buddyboss' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) ),
										esc_html( learndash_adjust_date_time_display( $ld_product->get_start_date() ) )
									);
								$status_html = sprintf(
									'<div class="ld-status ld-status-incomplete ld-third-background" data-ld-tooltip="%s">%s %s</div>',
									$tooltips,
									__( 'Pre-order', 'buddyboss' ),
									esc_html( $ld_seats_available_text )
								);
							} else {
								$tooltips = $ld_product->can_be_purchased()
									? sprintf(
										// translators: placeholder: course.
										esc_attr_x( 'Enroll in this %s to get access', 'placeholder: course', 'buddyboss' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) )
									)
									: sprintf(
										// translators: placeholder: course.
										esc_attr_x( 'This %s is not available', 'placeholder: course', 'buddyboss' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) )
									);
								$status_html = sprintf(
									'<div class="ld-status ld-status-incomplete ld-third-background" data-ld-tooltip="%s">%s %s</div>',
									$tooltips,
									__( 'Not Enrolled', 'buddyboss' ),
									esc_html( $ld_seats_available_text )
								);
							}
						} else {
							ob_start();
							learndash_status_bubble( $progress_status );
							$status_html = ob_get_clean();
						}
					} elseif ( is_user_logged_in() && isset( $has_access ) && $has_access ) {
						ob_start();
						learndash_status_bubble( $progress_status );
						$status_html = ob_get_clean();
					} elseif ( 'open' !== $course_pricing['type'] ) {
						$status_html = sprintf(
							'<div class="ld-status ld-status-incomplete ld-third-background">%s</div>',
							__( 'Not Enrolled', 'buddyboss' )
						);
					}
					if ( ! empty( $status_html ) ) {
						?>
						<div class="bb-rl-course-details-item">
							<i class="bb-icons-rl-wave-triangle"></i>
							<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Status', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php printf( '<div class="bb-course-status-content">%s</div>', $status_html ); ?>
							</div>
							</div>
						</div>
						<?php
					}
					?>

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
										'course_id' => $course_id,
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
					if ( ! empty( $course_certficate_link ) ) {
						learndash_get_template_part(
							'modules/alert.php',
							array(
								'type'    => 'success bb-rl-ld-alert-certificate',
								'icon'    => 'certificate',
								'message' => __( 'You\'ve earned a certificate!', 'buddyboss' ),
								'button'  => array(
									'url'    => $course_certficate_link,
									'icon'   => 'download',
									'label'  => __( 'Download Certificate', 'buddyboss' ),
									'target' => '_new',
								),
							),
							true
						);
					}
					?>
					<?php if ( ! empty( $lessons ) ) { ?>
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
								do_action( 'learndash-course-expand-before', $course_id, $user_id );

								$bb_lesson_container_ids = implode(
									' ',
									array_filter(
										array_map(
											function ( $bb_lesson_id ) use ( $user_id, $course_id ) {
												$bb_topics = learndash_get_topic_list( $bb_lesson_id, $course_id );
												$quizzes   = learndash_get_lesson_quiz_list( $bb_lesson_id, $user_id, $course_id );

												// Ensure we only include this ID if there is something to collapse/expand.
												if (
													empty( $bb_topics )
													&& empty( $quizzes )
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
								if ( $has_topics || $bb_has_lesson_quizzes ) {
									?>
									<button
											aria-controls="<?php echo esc_attr( $bb_lesson_container_ids ); ?>"
											class="ld-expand-button ld-primary-background"
											id="<?php echo esc_attr( 'ld-expand-button-' . $course_id ); ?>"
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
									if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_lessons_listing_main' ) ) {
										?>
										<script>
											jQuery( function () {
												setTimeout( function () {
													jQuery( "<?php echo esc_attr( '#ld-expand-button-' . $course_id ); ?>" ).trigger( 'click' );
												}, 1000 );
											} );
										</script>
										<?php
									}
								}

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
					}

					/**
					 * Identify if we should show the course content listing
					 *
					 * @var $show_course_content [bool]
					 */
					$show_course_content = ( ! $has_access && 'on' === $bb_course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

					if ( $show_course_content ) {
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

							learndash_get_template_part(
								'course/listing.php',
								array(
									'course_id'            => $course_id,
									'user_id'              => $user_id,
									'lessons'              => $lessons,
									'lesson_topics'        => $bb_lesson_topics,
									'quizzes'              => $quizzes,
									'has_access'           => $has_access,
									'course_pager_results' => $bb_course_pager_results,
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

						<?php
					}
					?>

					<div class="bb-rl-entry-content">
						<?php
						$course_content = get_the_content();
						if ( ! empty( $course_content ) ) {
							?>
							<h2><?php esc_html_e( 'About course', 'buddyboss' ); ?></h2>
							<?php echo wp_kses_post( $course_content ); ?>
							<?php
						}
						?>
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

	<?php if ( $bb_readylaunch->bb_is_sidebar_enabled_for_courses() ) : ?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div>
