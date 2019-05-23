<?php

$is_enrolled       = false;
$lession_list      = learndash_get_lesson_list( get_the_ID() );
$lessons_count     = sizeof( $lession_list );
$total_lessons     = $lessons_count > 1 ? sprintf( __( '%s Lessons', 'buddyboss-theme' ), $lessons_count ) : sprintf( __( '%s Lesson', 'buddyboss-theme' ), $lessons_count );
$current_user_id   = get_current_user_id();
$access_list       = learndash_get_course_meta_setting( $post->ID, 'course_access_list' );
$admin_enrolled    = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
$has_access        = sfwd_lms_has_access( get_the_ID(), $current_user_id );
$course_price      = trim( learndash_get_course_meta_setting( get_the_ID(), 'course_price' ) );
$course_price_type = learndash_get_course_meta_setting( get_the_ID(), 'course_price_type' );
$paypal_settings   = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
$completed         = 0;
$total             = false;
$class             = '';

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
	if ( $current_user_id == $member->ID ) {
		$is_enrolled = true;
		break;
	}
}

// if admins are enrolled
if ( current_user_can( 'administrator' ) && $admin_enrolled === 'yes' ) {
	$is_enrolled = true;
}

// $current_user = wp_get_current_user();
if ( is_user_logged_in() ) {
	$user_id = get_current_user_id();
} else {
	$user_id = 0;
}

$course_id = get_the_ID();
if ( empty( $course_id ) ) {
	$course_id = learndash_get_course_id();
}

if ( ! empty( $user_id ) ) {

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	$percentage = 0;
	$message    = '';

	if ( ( ! empty( $course_progress ) ) && ( isset( $course_progress[ $course_id ] ) ) && ( ! empty( $course_progress[ $course_id ] ) ) ) {
		if ( isset( $course_progress[ $course_id ]['completed'] ) ) {
			$completed = absint( $course_progress[ $course_id ]['completed'] );
		}

		if ( isset( $course_progress[ $course_id ]['total'] ) ) {
			$total = absint( $course_progress[ $course_id ]['total'] );
		}
	} else {
		$total = 0;
	}
}

// If $total is still false we calculate the total from course steps.
if ( false === $total ) {
	$total = learndash_get_course_steps_count( $course_id );
}

if ( $total > 0 ) {
	$percentage = intval( $completed * 100 / $total );
	$percentage = ( $percentage > 100 ) ? 100 : $percentage;
} else {
	$percentage = 0;
}


if ( ! empty( $course_price ) && ( $course_price_type == 'paynow' || $course_price_type == 'subscribe' ) ) {
	$class = 'bb-course-paid';
}
?>

<li class="item-entry">
	<div class="list-wrap">
		<div class="item-avatar">
			<a class="ld-set-cookie" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>" data-group-id="<?php echo esc_attr( ( bp_is_group_single() ? bp_get_current_group_id() : '' ) ); ?>" href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ): ?>
					<?php the_post_thumbnail('post-thumbnail', array('class'=> 'photo')); ?>
				<?php else: ?>
					<img src="<?php echo bp_learndash_url('/assets/images/mystery-course.png'); ?>" class="photo" />
				<?php endif; ?>
			</a>
		</div>

		<div class="item">
			<div class="item-block">
				<?php
				if( $lessons_count > 0 ) {
					echo '<div class="course-lesson-count">' . $total_lessons . '</div>';
				} else {
					echo '<div class="course-lesson-count">' . __( '0 Lessons', 'buddyboss-theme' ) . '</div>';
				}
				?>
				<h3 class="course-name">
					<a class="ld-set-cookie" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>" data-group-id="<?php echo esc_attr( ( bp_is_group_single() ? bp_get_current_group_id() : '' ) ); ?>" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h3>

				<?php

				if( function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option('learndash_course_author') ) {
					SFWD_LMS::get_template('course_list_course_author', compact( 'post' ), true );
				}

				if( is_user_logged_in() && isset($has_access) && $has_access ) { ?>

					<div class="course-progress-wrap">

						<?php learndash_get_template_part( 'modules/progress.php', array(
							'context'   =>  'course',
							'user_id'   =>  $current_user_id,
							'course_id' =>  get_the_ID()
						), true ); ?>

					</div>

				<?php } else { ?>
					<div class="bb-course-excerpt">
						<?php echo get_the_excerpt( get_the_ID() ); ?>
					</div>
				<?php }

				// Price
				if ( !empty( $course_price ) && $course_price_type !== 'closed' ) { ?>
					<div class="bb-course-footer bb-course-pay"><span class="course-fee"><?php echo learndash_integration_prepare_price_str( array( 'code' => $paypal_settings['paypal_currency'], 'value' => $course_price ) ); ?></span></div><?php
				}

				?>
			</div>
		</div>
	</div>
</li>
