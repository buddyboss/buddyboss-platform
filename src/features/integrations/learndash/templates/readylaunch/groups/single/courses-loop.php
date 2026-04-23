<?php
/**
 * LearnDash Group Courses Loop Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

// Get the course ID.
$course_id = get_the_ID();
if ( empty( $course_id ) ) {
	$course_id = learndash_get_course_id();
}
$lesson_list            = learndash_get_lesson_list( $course_id, array( 'num' => - 1 ) );
$lessons_count          = ! empty( $lesson_list ) ? count( $lesson_list ) : 0;
$user_id                = is_user_logged_in() ? get_current_user_id() : 0;
$access_list            = learndash_get_course_meta_setting( $post->ID, 'course_access_list' );
$admin_enrolled         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
$user_course_has_access = sfwd_lms_has_access( $course_id, $user_id );
$paypal_settings        = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
$class                  = '';
$is_enrolled            = $user_course_has_access ? true : false;

if ( ! is_array( $access_list ) ) {
	$access_list = array();
}

$result = array();
if ( ! empty( $access_list ) ) {
	$result = array();
	foreach ( $access_list as $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( empty( $user ) || ! $user->exists() ) {
			continue;
		}
		if ( is_multisite() && ! is_user_member_of_blog( $user->ID ) ) {
			continue;
		}
		$result[] = $user;
	}
}

$members = $result;
foreach ( $members as $member ) {
	if ( (int) $user_id === (int) $member->ID ) {
		$is_enrolled = true;
		break;
	}
}

// if admins are enrolled
if ( current_user_can( 'administrator' ) && 'yes' === $admin_enrolled ) {
	$is_enrolled = true;
}

$class = '';
if ( ! empty( $course_price ) && ( $course_price['type'] == 'paynow' || $course_price['type'] == 'subscribe' || $course_price['type'] == 'closed' ) ) {
	$class = 'bb-course-paid';
}

// Get course progress.
$course_progress = learndash_course_progress(
	array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);
if ( empty( $course_progress ) ) {
	$course_progress = array(
		'percentage' => 0,
		'completed'  => 0,
		'total'      => 0,
	);
}
$course_status = ( 100 === (int) $course_progress['percentage'] ) ? 'completed' : 'notcompleted';
if ( $course_progress['percentage'] > 0 && 100 !== $course_progress['percentage'] ) {
	$course_status = 'progress';
}

// Course data.
$course_price  = learndash_get_course_price( $course_id );
$course_status = learndash_course_status( $course_id, $user_id );

$course_excerpt            = get_the_excerpt( $course_id );
$course_excerpt_in_listing = '';
if ( ! empty( $course_excerpt ) ) {
	$course_excerpt_in_listing = wp_trim_words( $course_excerpt, 10, '...' );
}

$resume_link = get_permalink( $course_id );
if ( $is_enrolled ) {
	$user_course_last_step_id = learndash_user_progress_get_first_incomplete_step( $user_id, $course_id );
	if ( ! empty( $user_course_last_step_id ) ) {
		$user_course_last_step_id = learndash_user_progress_get_parent_incomplete_step( $user_id, $course_id, $user_course_last_step_id );
		$resume_link              = learndash_get_step_permalink( $user_course_last_step_id, $course_id );
	}
}

$is_completed = false;
$has_access   = false;
if ( 'sfwd-courses' === $post->post_type ) {
	$has_access   = sfwd_lms_has_access( $post->ID, $user_id );
	$is_completed = learndash_course_completed( $user_id, $post->ID );
} elseif ( 'groups' === $post->post_type ) {
	$has_access   = learndash_is_user_in_group( $user_id, $post->ID );
	$is_completed = learndash_get_user_group_completed_timestamp( $post->ID, $user_id );
} elseif ( 'sfwd-lessons' === $post->post_type || 'sfwd-topic' === $post->post_type ) {
	$parent_course_id = learndash_get_course_id( $post->ID );
	$has_access       = is_user_logged_in() && ! empty( $parent_course_id ) ? sfwd_lms_has_access( $post->ID, $user_id ) : false;
	if ( 'sfwd-lessons' === $post->post_type ) {
		$is_completed = learndash_is_lesson_complete( $user_id, $post->ID, $parent_course_id );
	} elseif ( 'sfwd-topic' === $post->post_type ) {
		$is_completed = learndash_is_topic_complete( $user_id, $post->ID, $parent_course_id );
	}
}

$button_text = '';
if ( $is_enrolled && 0 === $course_progress['percentage'] ) {
	$button_text = __( 'Continue', 'buddyboss' );
} elseif ( $has_access && $is_completed ) {
	$button_text = __( 'Completed', 'buddyboss' );
} else {
	$button_text = __( 'View Course', 'buddyboss' );
}
?>

<div class="bb-rl-course-card bb-rl-course-card--ldlms">
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-course-item' ); ?>>
		<div class="bb-rl-course-image">
			<a href="<?php the_permalink(); ?>">
				<?php
				if ( is_user_logged_in() && isset( $user_course_has_access ) && $user_course_has_access ) {
					if (
						(
							'open' === $course_price['type'] &&
							0 === (int) $course_progress['percentage']
						) ||
						(
							'open' !== $course_price['type'] &&
							$user_course_has_access &&
							0 === $course_progress['percentage']
						)
					) {
						echo '<div class="ld-status ld-status-progress ld-start-background bb-rl-ld-status">' .
							sprintf(
								/* translators: %s: Course label. */
								esc_html__( 'Start %s', 'buddyboss' ),
								esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
							) .
						'</div>';
					} else {
						learndash_status_bubble( $course_status );
					}
				} elseif ( 'free' === $course_price['type'] ) {
					echo '<div class="ld-status ld-status-incomplete ld-third-background">' . esc_html__( 'Free', 'buddyboss' ) . '</div>';
				} elseif ( 'open' !== $course_price['type'] ) {
					echo '<div class="ld-status ld-status-incomplete ld-third-background">' . esc_html__( 'Not Enrolled', 'buddyboss' ) . '</div>';
				} elseif ( 'open' === $course_price['type'] ) {
					echo '<div class="ld-status ld-status-progress ld-start-background bb-rl-ld-status">' .
						sprintf(
							/* translators: %s: Course label. */
							esc_html__( 'Start %s', 'buddyboss' ),
							esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
						) .
					'</div>';
				}
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'medium' );
				} else {
					?>
					<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Course placeholder image', 'buddyboss' ); ?>">
					<?php
				}
				?>
			</a>
		</div>

		<div class="bb-rl-course-card-content">
			<div class="bb-rl-course-body">
				<h2 class="bb-rl-course-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
				<div class="bb-rl-course-meta">
					<?php
					$course_category = get_the_terms( $course_id, 'ld_course_category' );
					if ( ! empty( $course_category ) ) {
						?>
						<div class="bb-rl-course-category">
							<?php
							$category_names = array_map(
								function ( $category ) {
									return esc_html( $category->name );
								},
								$course_category
							);

							$total_categories = count( $category_names );
							$max_display      = 1;

							if ( $total_categories <= $max_display ) {
								echo esc_html( implode( ', ', $category_names ) );
							} else {
								$visible_categories = array_slice( $category_names, 0, $max_display );
								$remaining_count    = $total_categories - $max_display;
								echo esc_html(
									sprintf(
									// translators: 1: comma-separated category names, 2: number of additional categories.
										__( '%1$s, + %2$d more', 'buddyboss' ),
										implode( ', ', $visible_categories ),
										$remaining_count
									)
								);
							}
							?>
						</div>
						<?php
					}
					if ( ! empty( $course_excerpt_in_listing ) ) {
						?>
						<div class="bb-rl-course-excerpt">
							<?php echo wp_kses_post( $course_excerpt_in_listing ); ?>
						</div>
						<?php
					}
					?>
					<div class="bb-rl-course-author">
						<?php
						$user_link = bp_core_get_user_domain( get_the_author_meta( 'ID' ) );
						if ( ! empty( $user_link ) ) {
							?>
							<a class="item-avatar bb-rl-author-avatar" href="<?php echo esc_url( $user_link ); ?>">
								<?php echo get_avatar( get_the_author_meta( 'email' ), 80, '', '', array() ); ?>
							</a>
							<?php
						}
						?>
						<span class="bb-rl-author-name">
							<?php
							$author_name = get_the_author_meta( 'display_name' );
							// translators: %s is the author name.
							printf( esc_html__( 'By %s', 'buddyboss' ), '<a href="' . esc_url( $user_link ) . '">' . esc_html( $author_name ) . '</a>' );
							?>
						</span>
					</div>
					<?php
					if ( $is_enrolled ) {
						?>
						<div class="bb-rl-course-status">
							<?php
							if ( ! empty( $course_progress ) ) {
								?>
								<div class="bb-rl-course-progress">
									<div class="bb-rl-course-progress-overview flex items-center">
										<span class="bb-rl-percentage">
											<?php
											echo wp_kses_post(
												sprintf(
												/* translators: 1: course progress percentage, 2: percentage symbol. */
													__( '<span class="bb-rl-percentage-figure">%1$s%2$s</span> Completed', 'buddyboss' ),
													(int) $course_progress['percentage'],
													'%'
												)
											);
											?>
										</span>
										<?php
										// Get completed steps.
										$completed_steps = ! empty( $course_progress['completed'] ) ? (int) $course_progress['completed'] : 0;

										// Output as "completed/total".
										if ( $course_progress['total'] > 0 ) {
											?>
											<span class="bb-rl-course-steps">
												<?php echo esc_html( $completed_steps . '/' . $course_progress['total'] ); ?>
											</span>
											<?php
										}
										?>
									</div>
									<div class="bb-rl-progress-bar">
										<div class="bb-rl-progress" style="width: <?php echo (int) $course_progress['percentage']; ?>%"></div>
									</div>
								</div>
								<?php
							}
							?>
							<div class="bb-rl-course-link-wrap">
								<a href="<?php echo esc_url( $resume_link ); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
									<?php echo esc_html( $button_text ); ?>
									<i class="bb-icons-rl-caret-right"></i>
								</a>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
			if ( ! $is_enrolled ) {
				?>
				<div class="bb-rl-course-footer">
					<div class="bb-rl-course-footer-meta">
						<?php
						if ( class_exists( 'LearnDash_Course_Reviews_Loader' ) && $course_id ) {
							$average = learndash_course_reviews_get_average_review_score( $course_id );

							// Get reviews.
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

							// Set default values if no average.
							$display_average = ( false !== $average ) ? $average : 0;
							?>
							<div class="bb-rl-course-review">
								<span class="star">
									<svg width="20" height="20" viewBox="0 0 20 20" fill="#FFC107" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
										<path d="M10 15l-5.878 3.09 1.122-6.545L.488 6.91l6.561-.955L10 0l2.951 5.955 6.561.955-4.756 4.635 1.122 6.545z" />
									</svg>
								</span>
								<span class="average"><?php echo esc_html( number_format( $display_average, 1 ) ); ?></span>
								<span class="count">(<?php echo esc_html( $review_count ); ?>)</span>
							</div>
							<?php
						}
						$currency = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();
						$price    = $course_price['price'];
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
								?>
								<div class="bb-rl-course-price">
									<span class="bb-rl-price">
										<span class="ld-currency">
											<?php echo wp_kses_post( $currency ); ?>
										</span>
										<?php echo wp_kses_post( $price ); ?>
									</span>
								</div>
								<?php
							}
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</article>
	<div class="bb-rl-course-card-popup">
		<div class="bb-rl-course-timestamp">
			<?php
			$updated_date = get_the_modified_date();
			// translators: %s is the updated date.
			printf( esc_html__( 'Updated: %s', 'buddyboss' ), esc_html( $updated_date ) );
			?>
		</div>
		<div class="bb-rl-course-popup-meta">
			<?php
			$total_lessons = (
			$lessons_count > 1
				? sprintf(
			/* translators: 1: plugin name, 2: action number 3: total number of actions. */
					__( '%1$s %2$s', 'buddyboss' ),
					$lessons_count,
					LearnDash_Custom_Label::get_label( 'lessons' )
				)
				: sprintf(
			/* translators: 1: plugin name, 2: action number 3: total number of actions. */
					__( '%1$s %2$s', 'buddyboss' ),
					$lessons_count,
					LearnDash_Custom_Label::get_label( 'lesson' )
				)
			);
			?>
			<span class="bb-rl-course-meta-tag"><?php echo esc_html( $total_lessons ); ?></span>
		</div>
		<div class="bb-rl-course-popup-caption">
			<?php
			echo wp_kses_post( $course_excerpt );
			?>
		</div>
		<div class="bb-rl-course-author">
			<h4><?php esc_html_e( 'Instructors', 'buddyboss' ); ?></h4>
			<?php
			$shared_instructor_ids = BB_Readylaunch_Learndash_Helper::instance()->bb_rl_get_course_instructor( $course_id );

			// Display all instructors.
			foreach ( $shared_instructor_ids as $instructor_id ) {
				$instructor = get_userdata( $instructor_id );
				if ( $instructor ) {
					$instructor_user_link = bp_core_get_user_domain( $instructor_id );
					?>
					<div class="bb-rl-instructor-item">
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
		<div class="bb-rl-course-popup-actions">
			<a href="<?php echo esc_url( $resume_link ); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
				<i class="bb-icons-rl-play"></i>
				<?php echo esc_html( $button_text ); ?>
			</a>
		</div>
	</div>
</div>

