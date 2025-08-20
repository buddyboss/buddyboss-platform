<?php
/**
 * LearnDash Single Course Template for ReadyLaunch.
 *
 * @since   BuddyBoss 2.9.00
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

if ( ! isset( $course_id ) ) {
	return;
}

$bb_rl_ld_helper = class_exists( 'BB_Readylaunch_Learndash_Helper' ) ? BB_Readylaunch_Learndash_Helper::instance() : null;

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
$course_progress = $bb_rl_ld_helper->bb_rl_get_courses_progress( $user_id );
$course_progress = isset( $course_progress[ $course_id ] ) ? $course_progress[ $course_id ] : 0;

$progress = learndash_course_progress(
	array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);

if ( empty( $progress ) ) {
	$progress = array(
		'percentage' => 0,
		'completed'  => 0,
		'total'      => 0,
	);
}
$progress_status = ( 100 === (int) $progress['percentage'] ) ? 'completed' : 'notcompleted';
if ( 0 < (int) $progress['percentage'] && 100 !== (int) $progress['percentage'] ) {
	$progress_status = 'progress';
}

// Get the ReadyLaunch instance to check if sidebar is enabled.
$bb_readylaunch = BB_Readylaunch::instance();

// Course data.
$course_pricing = learndash_get_course_price( $course_id );
$is_enrolled    = sfwd_lms_has_access( $course_id, $user_id );
$course_status  = learndash_course_status( $course_id, $user_id );
$ld_permalinks  = get_option( 'learndash_settings_permalinks', array() );
$course_slug    = isset( $ld_permalinks['courses'] ) ? $ld_permalinks['courses'] : 'courses';

// Get course steps.
$course_steps = learndash_get_course_steps( $course_id );
$lesson_count = array_column( $lessons, 'post' );
$topics_count = count(
	array_filter(
		$course_steps,
		function ( $step_id ) {
			return get_post_type( $step_id ) === 'sfwd-topic';
		}
	)
);

if ( ! empty( $lessons ) ) {
	foreach ( $lessons as $bb_lesson ) {
		$lesson_topics[ $bb_lesson['post']->ID ] = learndash_topic_dots( $bb_lesson['post']->ID, false, 'array', null, $course_id );
		if ( ! empty( $lesson_topics[ $bb_lesson['post']->ID ] ) ) {
			$has_topics = true;

			$topic_pager_args = array(
				'course_id' => $course_id,
				'lesson_id' => $bb_lesson['post']->ID,
			);

			$lesson_topics[ $bb_lesson['post']->ID ] = learndash_process_lesson_topics_pager( $lesson_topics[ $bb_lesson['post']->ID ], $topic_pager_args );
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

$is_completed = learndash_course_completed( $user_id, $course_id );

// Additional variables needed for course content listing.
$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons );
global $course_pager_results;

$resume_link = get_permalink( $course_id );
if ( $is_enrolled ) {
	$user_course_last_step_id = learndash_user_progress_get_first_incomplete_step( $user_id, $course_id );
	if ( ! empty( $user_course_last_step_id ) ) {
		$user_course_last_step_id = learndash_user_progress_get_parent_incomplete_step( $user_id, $course_id, $user_course_last_step_id );
		$resume_link              = learndash_get_step_permalink( $user_course_last_step_id, $course_id );
	}
}

$ld_product = null;
if ( class_exists( 'LearnDash\Core\Models\Product' ) && isset( $course_id ) ) {
	$ld_product = LearnDash\Core\Models\Product::find( (int) $course_id );
}

$course_video_embed    = get_post_meta( $course_id, '_buddyboss_lms_course_video', true );
$file_info             = pathinfo( $course_video_embed );
$course_video_duration = get_post_meta( $course_id, '_buddyboss_lms_course_video_duration', true );
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<article id="post-<?php echo esc_attr( get_the_ID() ); ?>" <?php post_class( array( 'bb-rl-learndash-course', 'bb-rl-lms-course' ) ); ?>>
			<header class="bb-rl-entry-header">
				<div class="bb-rl-course-banner flex">
					<div class="bb-rl-course-overview">
						<?php
						if ( taxonomy_exists( 'ld_course_category' ) ) {
							// category.
							$bb_course_cats = get_the_terms( $post->ID, 'ld_course_category' );
							if ( ! empty( $bb_course_cats ) ) {
								?>
								<div class="bb-rl-course-category">
									<?php foreach ( $bb_course_cats as $bb_course_cat ) { ?>
										<span class="bb-rl-course-category-item">
												<a title="<?php echo esc_attr( $bb_course_cat->name ); ?>" href="<?php printf( '%s/%s/?search=&filter-categories=%s', esc_url( home_url() ), esc_attr( $course_slug ), esc_attr( $bb_course_cat->slug ) ); ?>">
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
							if ( class_exists( 'LearnDash_Course_Reviews_Loader' ) && $course_id ) {
								$bb_average = learndash_course_reviews_get_average_review_score( $course_id );
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
							?>
							<div class="bb-rl-meta-item">
								<div class="bb-rl-author-name">
									<?php echo '<span class="bb-rl-author-name-label">' . esc_html__( 'By', 'buddyboss' ) . '</span> ' . esc_html( get_the_author_meta( 'first_name', $post->post_author ) ); ?>
								</div>
							</div>
						</div>
						<?php
						$course_pricing = wp_parse_args(
							$course_pricing,
							array(
								'type'             => LEARNDASH_DEFAULT_COURSE_PRICE_TYPE,
								'price'            => '',
								'interval'         => '',
								'frequency'        => '',
								'trial_price'      => '',
								'trial_interval'   => '',
								'trial_frequency'  => '',
								'repeats'          => '',
								'repeat_frequency' => '',
							)
						);

						if ( 'subscribe' === $course_pricing['type'] ) {
							if ( ( empty( $course_pricing['price'] ) ) || ( empty( $course_pricing['interval'] ) ) || ( empty( $course_pricing['frequency'] ) ) ) {
								$course_pricing['type']             = LEARNDASH_DEFAULT_COURSE_PRICE_TYPE;
								$course_pricing['interval']         = '';
								$course_pricing['frequency']        = '';
								$course_pricing['trial_price']      = '';
								$course_pricing['trial_interval']   = '';
								$course_pricing['trial_frequency']  = '';
								$course_pricing['repeats']          = '';
								$course_pricing['repeat_frequency'] = '';
							} elseif ( empty( $course_pricing['trial_price'] ) ) {
								$course_pricing['trial_interval']  = '';
								$course_pricing['trial_frequency'] = '';
							} elseif ( ( empty( $course_pricing['trial_interval'] ) ) || ( empty( $course_pricing['trial_frequency'] ) ) ) {
								$course_pricing['trial_price'] = '';
							}
						}

						$currency    = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();
						$price       = $course_pricing['price'];
						$trial_price = ! empty( $course_pricing['trial_price'] ) ? $course_pricing['trial_price'] : 0;

						if ( 'open' === $course_pricing['type'] || 'free' === $course_pricing['type'] ) {
							if ( 'open' === $course_pricing['type'] ) {
								echo '<span class="bb-course-type bb-course-type-open bb-rl-course-type-status">' . __( 'Open Registration', 'buddyboss' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo '<span class="bb-course-type bb-course-type-free bb-rl-course-type-status">' . __( 'Free', 'buddyboss' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} elseif ( 'closed' === $course_pricing['type'] ) {
							$learndash_payment_buttons = learndash_payment_buttons( $course );
							if ( empty( $learndash_payment_buttons ) ) {
								if ( false === $is_enrolled ) {
									if ( ! empty( $course_pricing['price'] ) ) {
										echo '<span class="bb-course-type bb-course-type-paynow bb-rl-course-price">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									echo '<span class="bb-rl-course-status-label bb-rl-notice--plain bb-rl-notice--error">' . esc_html__( 'This course is currently closed', 'buddyboss' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							} elseif ( ! empty( $course_pricing['price'] ) ) {
								echo '<span class="bb-course-type bb-course-type-paynow bb-rl-course-price">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} elseif ( 'paynow' === $course_pricing['type'] || 'subscribe' === $course_pricing['type'] ) {
							if ( false === $is_enrolled ) {
								if ( 'paynow' === $course_pricing['type'] ) {
									if ( ! empty( $course_pricing['price'] ) ) {
										echo '<span class="bb-course-type bb-course-type-paynow bb-rl-course-price">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								} else {
									$bb_trial_price = ! empty( $course_pricing['trial_price'] ) ? $course_pricing['trial_price'] : 0;
									if ( $bb_trial_price ) {
										?>
										<div class="bb-rl-composite-price">
											<div class="bb-rl-premium-price bb-rl-price-module">
												<span class="bb-rl-price">
													<span class="ld-currency"><?php echo wp_kses_post( $currency ); ?></span> 
													<?php echo esc_html( $bb_trial_price ); ?>
												</span>
												<span class="bb-rl-price-meta">
													<?php esc_html_e( 'Trial price for ', 'buddyboss' ); ?>
													<span class="bb-rl-meta-trial">
														<?php echo esc_html( $course_pricing['trial_interval'] ); ?>
														<?php echo esc_html( $course_pricing['trial_frequency'] ); ?>
													</span>
												</span>
											</div>
											<span class="bb-rl-separator bb-rl-separator--vertical"></span>
											<div class="bb-rl-full-price bb-rl-price-module">
												<span class="bb-rl-price">
													<span class="ld-currency"><?php echo wp_kses_post( $currency ); ?></span> 
													<?php echo wp_kses_post( $course_pricing['price'] ); ?>
												</span>
												<span class="bb-rl-price-meta">
													<?php esc_html_e( 'Full price every ', 'buddyboss' ); ?>
													<span class="bb-rl-meta-trial">
														<?php echo esc_html( $course_pricing['interval'] ); ?>
														<?php echo esc_html( $course_pricing['frequency'] ); ?>
													</span>
													<?php esc_html_e( 'afterward', 'buddyboss' ); ?>
												</span>
											</div>
										</div>
										<?php
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
										if ( '' === $course_pricing['price'] && 'subscribe' === $course_pricing['type'] ) {
											$recurring_label .= '<span class="bb-course-type bb-course-type-subscribe bb-rl-course-type-status">' . __( 'Free', 'buddyboss' ) . '</span>';
										} else {
											$recurring_label .= '<span class="bb-rl-course-price">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>';
										}
										$recurring_label .= '<span class="course-bill-cycle"> / ' . $recurring . ' ' . $bb_course_price_billing_t3 . '</span></span>';

										echo $recurring_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								}
							}
						}
						?>

						<div class="bb-rl-course-overview-footer">
							<?php
							if ( $is_enrolled && ! empty( $progress ) ) {
								?>
								<div class="bb-rl-course-progress">
									<div class="bb-rl-course-progress-overview flex items-center">
										<span class="bb-rl-percentage">
											<?php
											echo wp_kses_post(
												sprintf(
												/* translators: 1: course progress percentage, 2: percentage symbol. */
													__( '<span class="bb-rl-percentage-figure">%1$s%2$s</span> Completed', 'buddyboss' ),
													(int) $progress['percentage'],
													'%'
												)
											);
											?>
										</span>
										<?php
										// Get completed steps.
										$completed_steps = ! empty( $progress['completed'] ) ? (int) $progress['completed'] : 0;

										// Output as "completed/total".
										if ( $progress['total'] > 0 ) {
											?>
											<span class="bb-rl-course-steps">
														<?php echo esc_html( $completed_steps . '/' . $progress['total'] ); ?>
													</span>
											<?php
										}
										?>
									</div>
									<div class="bb-rl-progress-bar">
										<div class="bb-rl-progress" style="width: <?php echo (int) $progress['percentage']; ?>%"></div>
									</div>
								</div>
								<?php
							}
							?>
							<div class="bb-rl-course-action">
								<?php
								if ( empty( $course_progress ) && 100 > $course_progress ) {
									$btn_advance_class = 'btn-advance-start';
									$btn_advance_label = sprintf(
									/* translators: %s: Course label. */
										__( 'Start %s', 'buddyboss' ),
										LearnDash_Custom_Label::get_label( 'course' )
									);
								} elseif ( 100 === (int) $course_progress ) {
									$btn_advance_class = 'btn-advance-completed';
									$btn_advance_label = __( 'Completed', 'buddyboss' );
								} else {
									$btn_advance_class = 'btn-advance-continue';
									$btn_advance_label = __( 'Continue', 'buddyboss' );
								}

								if ( 0 === learndash_get_course_steps_count( $course_id ) && false !== $is_enrolled ) {
									$btn_advance_class .= ' btn-advance-disable';
								}

								$login_model           = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
								$login_url             = apply_filters( 'learndash_login_url', ( 'yes' === $login_model ? '#login' : wp_login_url( get_the_permalink( $course_id ) ) ) );
								$learndash_login_modal = apply_filters( 'learndash_login_modal', true, $course_id, $user_id ) && ! is_user_logged_in() && 'open' !== $course_pricing['type'];

								// Add proper checks for $ld_product.
								if ( $ld_product && $ld_product instanceof \LearnDash\Core\Models\Product ) {
									$learndash_login_modal = $learndash_login_modal && $ld_product->can_be_purchased();
								}

								// Determine button class and text based on conditions.
								$button_class = 'learndash_join_button';
								$button_class .= ! empty( $btn_advance_class ) ? ' ' . $btn_advance_class : '';

								$button_html = '';
								if ( 'open' === $course_pricing['type'] || 'free' === $course_pricing['type'] ) {
									if ( $learndash_login_modal ) {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $login_url ),
											__( 'Login to Enroll', 'buddyboss' )
										);
									} elseif ( 'free' === $course_pricing['type'] && false === $is_enrolled ) {
										$button_html = learndash_payment_buttons( $course );
									} else {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $resume_link ),
											esc_html( $btn_advance_label )
										);
									}
								} elseif ( 'closed' === $course_pricing['type'] ) {
									$learndash_payment_buttons = learndash_payment_buttons( $course );
									if ( empty( $learndash_payment_buttons ) && false !== $is_enrolled ) {
										$button_html = sprintf(
											'<a href="%s" class="btn-advance ld-primary-background">%s</a>',
											esc_url( $resume_link ),
											esc_html( $btn_advance_label )
										);
									} else {
										$button_html = $learndash_payment_buttons;
									}
								} elseif ( 'paynow' === $course_pricing['type'] || 'subscribe' === $course_pricing['type'] ) {
									if ( false === $is_enrolled ) {
										$meta                    = get_post_meta( $course_id, '_sfwd-courses', true );
										$course_pricing['type']  = $meta['sfwd-courses_course_price_type'];
										$course_pricing['price'] = $meta['sfwd-courses_course_price'];

										if ( 'subscribe' === $course_pricing['type'] && '' === $course_pricing['price'] ) {
											$button_text = ! empty( $custom_button_label )
												? esc_attr( $custom_button_label )
												: LearnDash_Custom_Label::get_label( 'button_take_this_course' );

											$button_html = sprintf(
												'<form method="post">
													<input type="hidden" value="%d" name="course_id" />
													<input type="hidden" name="course_join" value="%s" />
													<input type="submit" value="%s" class="btn-join" id="btn-join" />
												</form>',
												$post->ID,
												wp_create_nonce( 'course_join_' . get_current_user_id() . '_' . $post->ID ),
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
									/* translators: %s: Button class. */
									printf( '<div class="%s">%s</div>', esc_attr( $button_class ), $button_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>
							<div class="bb-rl-course-enrolled">
								<?php
								if ( $bb_rl_ld_helper ) {
									$bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
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
						<?php
						if ( '' !== $course_video_embed ) {
							// Get the feature image for video poster.
							$feature_image_url = '';
							$feature_image_id  = get_post_thumbnail_id( $course_id );

							if ( $feature_image_id ) {
								$feature_image_url = wp_get_attachment_image_url( $feature_image_id, 'full' );
							} else {
								// Fallback to default placeholder.
								$feature_image_url = buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg';
							}

							if ( wp_oembed_get( $course_video_embed ) ) {
								// For oEmbed videos (YouTube, Vimeo, etc.), show feature image with play button overlay.
								?>
								<div class="bb-rl-video-preview-container">
									<div class="bb-rl-video-preview-image" style="background-image: url('<?php echo esc_url( $feature_image_url ); ?>');">
										<div class="bb-rl-video-play-overlay">
											<i class="bb-icons-rl-play"></i>
										</div>
										<?php if ( ! empty( $course_video_duration ) ) : ?>
											<div class="bb-rl-video-duration">
												<span class="bb-rl-video-duration-text"><?php echo esc_html( $course_video_duration ); ?></span>
											</div>
										<?php endif; ?>
									</div>
									<div class="bb-rl-video-embed-container" style="display: none;">
										<?php echo wp_oembed_get( $course_video_embed ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								</div>
								<?php
							} elseif ( isset( $file_info['extension'] ) && 'mp4' === $file_info['extension'] ) {
								// For MP4 videos, use the poster attribute.
								?>
								<div class="bb-rl-video-preview-container">
									<video width="100%" controls poster="<?php echo esc_url( $feature_image_url ); ?>">
										<source src="<?php echo esc_url( $course_video_embed ); ?>" type="video/mp4">
										<?php esc_html_e( 'Your browser does not support HTML5 video.', 'buddyboss' ); ?>
									</video>
								</div>
								<?php
							} else {
								?>
								<div class="bb-rl-video-preview-container bb-rl-video-preview-container--error">
									<?php
									esc_html_e( 'Video format is not supported, use Youtube video or MP4 format.', 'buddyboss' );
									?>
								</div>
								<?php
							}
						} elseif ( has_post_thumbnail() ) {
							?>
							<div class="bb-rl-course-featured-image">
								<?php the_post_thumbnail( 'full' ); ?>
							</div>
							<?php
						} else {
							?>
							<div class="bb-rl-course-featured-image">
								<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Course placeholder image', 'buddyboss' ); ?>">
							</div>
							<?php
						}
						?>
					</div>
				</div>

				<div class="bb-rl-course-details">
					<?php
					$status_html = '';

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
								<?php printf( '<div class="bb-course-status-content">%s</div>', wp_kses_post( $status_html ) ); ?>
							</div>
							</div>
						</div>
						<?php
					}

					$formatted_duration = $bb_rl_ld_helper->bb_rl_format_course_expiration_time( $course_id );
					if ( $formatted_duration ) {
						?>
						<div class="bb-rl-course-details-item">
							<i class="bb-icons-rl-timer"></i>
							<div>
								<div class="bb-rl-course-details-label">
									<?php esc_html_e( 'Duration', 'buddyboss' ); ?>
								</div>
								<div class="bb-rl-course-details-value">
									<?php
									echo wp_kses_post( $formatted_duration );
									?>
								</div>
							</div>
						</div>
						<?php
					}
					?>

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
								$bb_enrolled_users = $bb_rl_ld_helper->bb_rl_ld_get_enrolled_users_data(
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

					<?php
					if ( ! $formatted_duration && isset( $course_settings['course_disable_lesson_progression'] ) ) {
						$progression_text = $course_settings['course_disable_lesson_progression'] ? __( 'Free form', 'buddyboss' ) : __( 'Linear', 'buddyboss' );
						?>
						<div class="bb-rl-course-details-item">
							<i class="bb-icons-rl-clock-countdown"></i>
							<div>
								<div class="bb-rl-course-details-label">
									<?php esc_html_e( 'Progression', 'buddyboss' ); ?>
								</div>
								<div class="bb-rl-course-details-value">
									<?php
									echo wp_kses_post( $progression_text );
									?>
								</div>
							</div>
						</div>
						<?php
					}
					?>

					<div class="bb-rl-course-details-item">
						<i class="bb-icons-rl-arrows-clockwise"></i>
						<div>
							<div class="bb-rl-course-details-label">
								<?php esc_html_e( 'Update', 'buddyboss' ); ?>
							</div>
							<div class="bb-rl-course-details-value">
								<?php
								echo esc_html( $bb_rl_ld_helper->bb_rl_get_course_latest_modified_date( $course_id, 'default' ) );
								?>
							</div>
						</div>
					</div>
				</div>
			</header>

			<div class="bb-rl-course-content">
				<div class="bb-rl-course-content-inner">
					<?php
					/**
					 * Identify if we should show the course content listing
					 *
					 * @var $show_course_content [bool]
					 */
					$show_course_content = ( ! $has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );
					if ( $show_course_content ) {

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

						if ( ! empty( $lessons ) ) {
							?>
							<div class="bb-rl-course-content-header">
								<div class="bb-rl-course-content-header-inner">
									<?php
									/**
									 * Fires before the course heading.
									 *
									 * @since BuddyBoss 2.9.00
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
									 * @since BuddyBoss 2.9.00
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
									 * @since BuddyBoss 2.9.00
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
												array_keys( $lesson_topics )
											)
										)
									);
									?>

									<?php
									// Only display if there is something to expand.
									if ( $has_topics || $has_lesson_quizzes ) {
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
										 * @since BuddyBoss 2.9.00
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
									 * @since BuddyBoss 2.9.00
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
						?>

						<div class="ld-item-list ld-lesson-list bb-rl-ld-lesson-list">

							<?php
							/**
							 * Fires before the course content listing
							 *
							 * @since BuddyBoss 2.9.00
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
							 * @since BuddyBoss 2.9.00
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
						<div class="learndash-wrapper">
							<?php
							/** This filter is documented in themes/legacy/templates/course.php */
							echo apply_filters( 'ld_after_course_status_template_container', '', learndash_course_status_idx( $course_status ), $course_id, $user_id );

							/**
							 * Content tabs
							 */
							learndash_get_template_part(
								'modules/tabs.php',
								array(
									'course_id' => $course_id,
									'post_id'   => get_the_ID(),
									'user_id'   => $user_id,
									'content'   => $content,
									'materials' => $materials,
									'context'   => 'course',
								),
								true
							);
							?>
						</div> <!-- /.learndash-wrapper -->
					</div>
				</div> <!-- /.bb-rl-course-content-inner -->
				<div class="bb-rl-course-content-sidebar bb-rl-widget-sidebar">
					<?php
					if ( has_excerpt( $course_id ) ) {
						?>
						<div class="widget">
							<h2 class="widget-title">
								<?php esc_html_e( 'Summary', 'buddyboss' ); ?>
							</h2>
							<div class="widget-content">
								<div class="bb-rl-course-summary-inner">
									<div class="bb-rl-course-summary-excerpt">
										<?php echo get_the_excerpt( $course_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output ?>
									</div>
									<div class="bb-rl-course-summary-instructor">
										<h3><?php esc_html_e( 'Instructor', 'buddyboss' ); ?></h3>
										<?php
										$shared_instructor_ids = $bb_rl_ld_helper->bb_rl_get_course_instructor( $course_id );

										// Display all instructors.
										foreach ( $shared_instructor_ids as $instructor_id ) {
											$instructor = get_userdata( $instructor_id );
											if ( $instructor ) {
												$instructor_user_link = bp_core_get_user_domain( $instructor_id );
												?>
												<div class="bb-rl-course-summary-instructor-item">
													<a class="item-avatar bb-rl-author-avatar" href="<?php echo esc_url( $instructor_user_link ); ?>">
														<?php echo get_avatar( $instructor_id, 32 ); ?>
													</a>
													<span class="bb-rl-author-name">
														<?php
														if ( ! empty( $instructor_user_link ) ) {
															echo '<a href="' . esc_url( $instructor_user_link ) . '">' . esc_html( $instructor->display_name ) . '</a>';
														} else {
															echo esc_html( $instructor->display_name );
														}
														?>
													</span>
												</div>
												<?php
											}
										}
										?>
									</div>
								</div>
							</div>
						</div>
						<?php
					}
					?>

					<div class="widget">
						<h2 class="widget-title">
							<?php esc_html_e( 'Recently enrolled', 'buddyboss' ); ?>
						</h2>
						<div class="widget-content">
							<?php
							if ( $bb_rl_ld_helper ) {
								$bb_rl_ld_helper->bb_rl_ld_get_enrolled_users(
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

			<div class="bb-rl-course-content-comments">
				<?php
				// If comments are open or we have at least one comment, load up the comment template.
				$focus_mode         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );
				$post_type_comments = learndash_post_type_supports_comments( $post->post_type );
				if ( is_user_logged_in() && 'yes' === $focus_mode && comments_open() ) {
					learndash_get_template_part(
						'focus/comments.php',
						array(
							'course_id' => $course_id,
							'user_id'   => $user_id,
							'context'   => 'focus',
						),
						true
					);
				} elseif ( true === $post_type_comments ) {
					if ( comments_open() ) :
						bp_get_template_part( 'learndash/ld30/comments' );
					endif;
				}
				?>
			</div>
		</article>
	</main>

	<?php if ( $bb_readylaunch->bb_is_sidebar_enabled_for_courses() ) : ?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
	<?php
	// Load login modal for non-logged in users.
	if ( ! is_user_logged_in() ) {
		global $login_model_load_once;
		$login_model_load_once      = false;
		$learndash_login_model_html = learndash_get_template_part( 'modules/login-modal.php', array(), false );
		if ( false !== $learndash_login_model_html ) {
			echo '<div class="learndash-wrapper learndash-wrapper-login-modal ld-modal-closed">' . $learndash_login_model_html . '</div>';
		}
	}
	?>
</div>
