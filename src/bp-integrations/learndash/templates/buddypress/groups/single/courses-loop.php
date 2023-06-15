<?php

$is_enrolled            = false;
$lesson_list            = learndash_get_lesson_list( get_the_ID(), array( 'num' => - 1 ) );
$current_user_id        = get_current_user_id();
$access_list            = learndash_get_course_meta_setting( $post->ID, 'course_access_list' );
$admin_enrolled         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
$user_course_has_access = sfwd_lms_has_access( get_the_ID(), $current_user_id );
$course_price           = trim( learndash_get_course_meta_setting( get_the_ID(), 'course_price' ) );
$course_price_type      = learndash_get_course_meta_setting( get_the_ID(), 'course_price_type' );
$paypal_settings        = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
$completed              = 0;
$total                  = false;
$class                  = '';

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

$class = '';
if ( ! empty( $course_price ) && ( $course_price_type == 'paynow' || $course_price_type == 'subscribe' || $course_price_type == 'closed' ) ) {
	$class = 'bb-course-paid';
}
?>

<li class="bb-course-item-wrap">
    <div class="bb-cover-list-item <?php echo $class; ?>">
        <div class="bb-course-cover">
            <a class="ld-set-cookie bb-cover-wrap" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>" data-group-id="<?php echo esc_attr( ( bp_is_group_single() ? bp_get_current_group_id() : '' ) ); ?>" title="<?php the_title_attribute(); ?>" href="<?php the_permalink(); ?>" class="bb-cover-wrap">
				<?php
				$progress = learndash_course_progress( array(
					'user_id'   => $current_user_id,
					'course_id' => $course_id,
					'array'     => true,
				) );

				$status = isset( $progress['percentage'] ) && ( 100 === absint( $progress['percentage'] ) ) ? 'completed' : 'notcompleted';

				if ( isset( $progress['percentage'] ) && $progress['percentage'] > 0 && $progress['percentage'] !== 100 ) {
					$status = 'progress';
				}

				if ( is_user_logged_in() && isset( $user_course_has_access ) && $user_course_has_access ) {

					if ( ( isset( $course_pricing['type'] ) && $course_pricing['type'] === 'open' && isset( $progress['percentage'] ) && $progress['percentage'] === 0 ) || ( isset( $course_pricing['type'] ) && $course_pricing['type'] !== 'open' && $user_course_has_access && isset( $progress['percentage'] ) && $progress['percentage'] === 0 ) ) {

						echo '<div class="ld-status ld-status-progress ld-primary-background">' . __( 'Start ', 'buddyboss' ) . sprintf( __( '%s', 'buddyboss' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</div>';

					} else {

						learndash_status_bubble( $status );

					}

				} elseif ( isset( $course_pricing['type'] ) && 'free' === $course_pricing['type'] ) {

					echo '<div class="ld-status ld-status-incomplete ld-third-background">' . __( 'Free', 'buddyboss' ) . '</div>';

				} elseif ( isset( $course_pricing['type'] ) && 'open' !== $course_pricing['type'] ) {

					echo '<div class="ld-status ld-status-incomplete ld-third-background">' . __( 'Not Enrolled', 'buddyboss' ) . '</div>';

				} elseif ( isset( $course_pricing['type'] ) && 'open' === $course_pricing['type'] ) {

					echo '<div class="ld-status ld-status-progress ld-primary-background">' . __( 'Start ', 'buddyboss' ) . sprintf( __( '%s', 'buddyboss' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</div>';

				}

				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'medium' );
				} ?>
            </a>
        </div>
        <div class="bb-card-course-details">
			<?php
			$lessons_count = sizeof( $lesson_list );
			$total_lessons = (
			$lessons_count > 1
				? sprintf(
				__( '%1$s %2$s', 'buddyboss' ),
				$lessons_count,
				LearnDash_Custom_Label::get_label( 'lessons' )
			)
				: sprintf(
				__( '%1$s %2$s', 'buddyboss' ),
				$lessons_count,
				LearnDash_Custom_Label::get_label( 'lesson' )
			)
			);

			if ( $lessons_count > 0 ) {
				echo '<div class="course-lesson-count">' . $total_lessons . '</div>';
			} else {
				echo '<div class="course-lesson-count">' . sprintf( __( '0 %s', 'buddyboss' ), LearnDash_Custom_Label::get_label( 'lessons' ) ) . '</div>';
			}
			?>
            <h2 class="bb-course-title">
                <a title="<?php the_title_attribute(); ?>" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>

			<?php
			if ( buddyboss_theme_get_option( 'learndash_course_author' ) ) {
				SFWD_LMS::get_template( 'course_list_course_author', compact( 'post' ), true );
			}

			if ( is_user_logged_in() && isset( $user_course_has_access ) && $user_course_has_access ) { ?>

                <div class="course-progress-wrap">

					<?php learndash_get_template_part( 'modules/progress.php',
						array(
							'context'   => 'course',
							'user_id'   => $current_user_id,
							'course_id' => $course_id,
						),
						true ); ?>

                </div>

			<?php } else { ?>
                <div class="bb-course-excerpt">
					<?php echo get_the_excerpt( $course_id ); ?>
                </div>
			<?php }

			// Price
			if ( ! empty( $course_price ) && empty( $is_enrolled ) ) { ?>
                <div class="bb-course-footer bb-course-pay">
                <span class="course-fee">
                        <?php
                        if ( $course_pricing['type'] !== 'closed' ):
	                        echo wp_kses_post( '<span class="ld-currency">' . learndash_30_get_currency_symbol() . '</span> ' );
                        endif;

                        echo wp_kses_post( $course_pricing['price'] ); ?>
                    </span>
                </div><?php
			}
			?>
        </div>
    </div>
</li>
