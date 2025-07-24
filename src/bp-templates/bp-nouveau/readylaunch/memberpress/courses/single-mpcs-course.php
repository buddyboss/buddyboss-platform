<?php
/**
 * Template for single course page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/single-mpcs-course.php.
 *
 * @since 2.9.00
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses\helpers;
use memberpress\courses\models as models;

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

				<div class="bb-rl-course-meta bb-rl-course-meta--updated">
					<div class="bb-rl-course-meta-label">
						<?php echo esc_html__( 'This course was last updated on', 'buddyboss' ); ?>
					</div>
					<div class="bb-rl-course-meta-value">
						<?php
						$course_update_date = BB_Readylaunch_Memberpress_Courses_Helper::bb_rl_mpcs_get_course_update_date( $course->ID, get_option( 'date_format' ) );
						echo esc_html( $course_update_date );
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
					<?php if ( ! empty( models\Lesson::get_thumbnail( $post ) ) ) { ?>
						<a href="<?php the_permalink(); ?>" alt="<?php the_title_attribute(); ?>" aria-label="<?php the_title_attribute(); ?>">
							<img src="<?php echo esc_url( models\Lesson::get_thumbnail( $post ) ); ?>" alt="">
						</a>
					<?php } else { ?>
						<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Course placeholder image', 'buddyboss' ); ?>">
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

	<div class="bb-rl-course-content">
		<div class="bb-rl-course-content-inner">
			<div class="bb-rl-course-content-panel">
				<div id="mpcs-main" class="bb-rl-course-content-module">
					<?php
					setup_postdata( $post->ID );
					echo wp_kses_post( BB_Readylaunch_Memberpress_Courses_Helper::bb_rl_mpcs_render_course_tab_menu() );
					the_content();
					?>
				</div>
				<div class="bb-rl-course-content-comments">
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
