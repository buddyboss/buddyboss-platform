<?php
/**
 * LearnDash LD30 Displays a Course Prev/Next navigation.
 *
 * Available Variables:
 *
 * $course_id        : (int) ID of Course
 * $course_step_post : (object) WP_Post instance of lesson/topic post
 * $user_id          : (int) ID of User
 * $course_settings  : (array) Settings specific to current course
 * $can_complete     : (bool) Can the user mark this lesson/topic complete?
 * $context          : (string) Context of the usage. Either 'lesson', 'topic' or 'focus' use for Focus Mode header
 * navigation.
 *
 * @since 3.0.0
 * @version 4.11.0
 *
 * @since BuddyBoss 2.9.00
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $can_complete ) ) {
	$can_complete = false;
}

// TODO @37designs this is a bit confusing still, as you can still navigate left / right on lessons even with topics.
if ( ( isset( $course_step_post ) ) && ( is_a( $course_step_post, 'WP_Post' ) ) && ( in_array( $course_step_post->post_type, learndash_get_post_types( 'course' ), true ) ) ) {
	if ( learndash_get_post_type_slug( 'lesson' ) === $course_step_post->post_type ) {
		$parent_id = absint( $course_id );
	} else {
		$parent_id = learndash_course_get_single_parent_step( $course_id, $course_step_post->ID );
	}
} else {
	$parent_id = ( get_post_type() === 'sfwd-lessons' ? absint( $course_id ) : learndash_course_get_single_parent_step( $course_id, get_the_ID() ) );
}

// If parent ID is empty then the parent is the course.
if ( empty( $parent_id ) ) {
	$parent_id = absint( $course_id );
}

$learndash_previous_step_id = learndash_previous_post_link( null, 'id', $course_step_post );
$learndash_next_step_id     = '';

$button_class = 'ld-button ' . ( 'focus' === $context ? 'ld-button-transparent' : '' );

/*
 * See details for filter 'learndash_show_next_link' at https://developers.learndash.com/hook/learndash_show_next_link/
 *
 * @since version 2.3
 */

$current_complete = false;

if ( ( empty( $course_settings ) ) && ( ! empty( $course_id ) ) ) {
	$course_settings = learndash_get_setting( $course_id );
}

if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ) === $course_step_post->post_type ) {
	$current_complete = learndash_is_topic_complete( $user_id, $course_step_post->ID, $course_id );
} elseif ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ) === $course_step_post->post_type ) {
	$current_complete = learndash_is_lesson_complete( $user_id, $course_step_post->ID, $course_id );
} elseif ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ) === $course_step_post->post_type ) {
	$current_complete = learndash_is_quiz_complete( $user_id, $course_step_post->ID, $course_id );
}

if ( learndash_lesson_hasassignments( $course_step_post ) ) {
	$user_assignments     = learndash_get_user_assignments( $course_step_post->ID, $user_id, absint( $course_id ), 'ids' );
	$approved_assignments = learndash_assignment_list_approved( $user_assignments, $course_step_post->ID, $user_id );
	if ( ! $approved_assignments ) {
		$current_complete = false;
	}
}

$learndash_maybe_show_next_step_link = $current_complete;
// if ( ( isset( $course_settings['course_disable_lesson_progression'] ) ) && ( 'on' === $course_settings['course_disable_lesson_progression'] ) ) {

$course_lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
if ( ! $course_lesson_progression_enabled ) {
	$learndash_maybe_show_next_step_link = true;
}

if ( $learndash_maybe_show_next_step_link !== true ) {
	$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_course_progression' );
	if ( true === $bypass_course_limits_admin_users ) {
		$learndash_maybe_show_next_step_link = true;
	}
}

/**
 * Filters whether to show the next link in the course navigation.
 *
 * @since 2.3.0
 *
 * @param bool $show_next_link Whether to show next link.
 * @param int  $user_id        User ID.
 * @param int  $step_id        ID of the lesson/topic post.
 */
$learndash_maybe_show_next_step_link = apply_filters( 'learndash_show_next_link', $learndash_maybe_show_next_step_link, $user_id, $course_step_post->ID );

// Only complete lessons/topics or external quizzes.
if (
	! in_array(
		$course_step_post->post_type,
		learndash_get_post_type_slug(
			array(
				LDLMS_Post_Types::LESSON,
				LDLMS_Post_Types::TOPIC,
			)
		),
		true
	)
	&& ! (
		$course_step_post->post_type === LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ )
		&& learndash_course_steps_is_external( $course_step_post->ID )
	)
) {
	$can_complete                        = false;
	$current_complete                    = false;
	$learndash_maybe_show_next_step_link = false;
}

if ( true === (bool) $learndash_maybe_show_next_step_link ) {
	$learndash_next_step_id = learndash_next_post_link( null, 'id', $course_step_post );
} elseif ( ( ! is_user_logged_in() ) && ( empty( $learndash_next_step_id ) ) ) {
	$learndash_next_step_id = learndash_next_post_link( null, 'id', $course_step_post );

	if ( ! empty( $learndash_next_step_id ) ) {
		if ( ! learndash_is_sample( $learndash_next_step_id ) ) {
			if ( ( ! isset( $course_settings['course_price_type'] ) ) || ( 'open' !== $course_settings['course_price_type'] ) ) {
				$learndash_next_step_id = '';
			}
		}
	}
}

/**
 * Filters to override next step post ID.
 *
 * @since 3.1.2
 *
 * @param int $learndash_next_step_id The next step post ID.
 * @param int $course_step_post       The current step WP_Post ID.
 * @param int $course_id              The current Course ID.
 * @param int $user_id                The current User ID.
 *
 * @return int $learndash_next_step_id
 */
$learndash_next_step_id = apply_filters( 'learndash_next_step_id', $learndash_next_step_id, $course_step_post->ID, $course_id, $user_id );

/**
 * Check if we need to show the Mark Complete form. see LEARNDASH-4722
 */
$parent_lesson_id = 0;
if ( $course_step_post->post_type == 'sfwd-lessons' ) {
	$parent_lesson_id = $course_step_post->ID;
} elseif ( $course_step_post->post_type == 'sfwd-topic' || $course_step_post->post_type == 'sfwd-quiz' ) {
	if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		$parent_lesson_id = learndash_course_get_single_parent_step( $course_id, $course_step_post->ID );
	} else {
		$parent_lesson_id = learndash_get_setting( $course_step_post, 'lesson' );
	}
}
if ( ! empty( $parent_lesson_id ) ) {
	$lesson_access_from = ld_lesson_access_from( $parent_lesson_id, $user_id, $course_id );
	if ( ( empty( $lesson_access_from ) ) || ( ! empty( $bypass_course_limits_admin_users ) ) ) {
		$complete_button = learndash_mark_complete( $course_step_post );
	} else {
		$complete_button = '';

	}
} else {
	$complete_button = learndash_mark_complete( $course_step_post );
}

if ( ( true === $current_complete ) && ( is_a( $course_step_post, 'WP_Post' ) ) ) {
	$incomplete_button = learndash_show_mark_incomplete( $course_step_post );
} else {
	$incomplete_button = '';
}

?>
<nav class="bb-rl-ld-module-footer bb-rl-topic-footer">
	<div class="bb-rl-ld-module-actions bb-rl-topic-actions">

		<div class="ld-content-actions">

			<?php
			/**
			 * Fires before the course steps (all locations).
			 *
			 * @since 3.0.0
			 *
			 * @param string|false $post_type Post type slug.
			 * @param int          $course_id Course ID.
			 * @param int          $user_id   User ID.
			 */
			do_action( 'learndash-all-course-steps-before', get_post_type(), $course_id, $user_id );

			/**
			 * Fires before the course steps for any context.
			 *
			 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
			 * such as `course`, `lesson`, `topic`, `quiz`, etc.
			 *
			 * @since 3.0.0
			 *
			 * @param string|false $post_type Post type slug.
			 * @param int          $course_id Course ID.
			 * @param int          $user_id   User ID.
			 */
			do_action( 'learndash-' . $context . '-course-steps-before', get_post_type(), $course_id, $user_id );
			// $learndash_current_post_type = get_post_type();
			?>
			<div class="ld-content-action
			<?php
			if ( ! $learndash_previous_step_id ) :
				?>
				ld-empty<?php endif; ?>">
				<?php if ( $learndash_previous_step_id ) : ?>
					<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $learndash_previous_step_id, $course_id ) ); ?>">
						<?php if ( is_rtl() ) { ?>
							<span class="ld-icon ld-icon-arrow-right"></span>
						<?php } else { ?>
							<span class="ld-icon ld-icon-arrow-left"></span>
						<?php } ?>
						<span class="ld-text"><?php echo esc_html( learndash_get_label_course_step_previous( get_post_type( $learndash_previous_step_id ) ) ); ?></span>
					</a>
				<?php endif; ?>
			</div>

			<?php

			if ( $parent_id && 'focus' !== $context ) :
				if ( $learndash_maybe_show_next_step_link ) :
					?>
					<div class="ld-content-action">
						<?php
						if ( ( true === $can_complete ) && ( true !== $current_complete ) && ( ! empty( $complete_button ) ) ) :
							echo learndash_mark_complete( $course_step_post );
						elseif ( ( true === $can_complete ) && ( true === $current_complete ) && ( ! empty( $incomplete_button ) ) ) :
							echo $incomplete_button;
							?>

						<?php endif; ?>
						<a href="<?php echo esc_url( learndash_get_step_permalink( $parent_id, $course_id ) ); ?>" class="ld-primary-color ld-course-step-back"><?php echo learndash_get_label_course_step_back( get_post_type( $parent_id ) ); ?></a>
					</div>
					<div class="ld-content-action
					<?php
					if ( ( ! $learndash_next_step_id ) ) :
						?>
						ld-empty<?php endif; ?>">
						<?php if ( $learndash_next_step_id ) : ?>
							<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $learndash_next_step_id, $course_id ) ); ?>">
								<span class="ld-text"><?php echo learndash_get_label_course_step_next( get_post_type( $learndash_next_step_id ) ); ?></span>
								<?php if ( is_rtl() ) { ?>
									<span class="ld-icon ld-icon-arrow-left"></span>
								<?php } else { ?>
									<span class="ld-icon ld-icon-arrow-right"></span>
								<?php } ?>
							</a>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<a href="<?php echo esc_attr( learndash_get_step_permalink( $parent_id, $course_id ) ); ?>" class="ld-primary-color"><?php echo learndash_get_label_course_step_back( get_post_type( $parent_id ) ); ?></a>
					<div class="ld-content-action
					<?php
					if ( ( ! $can_complete ) && ( ! $learndash_next_step_id ) ) :
						?>
						ld-empty<?php endif; ?>">
						<?php
						if ( ( true === $can_complete ) && ( true !== $current_complete ) && ( ! empty( $complete_button ) ) ) :
							echo $complete_button;
						elseif ( $learndash_next_step_id ) :
							?>
							<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_attr( learndash_get_step_permalink( $learndash_next_step_id, $course_id ) ); ?>">
								<span class="ld-text"><?php echo learndash_get_label_course_step_next( get_post_type( $learndash_next_step_id ) ); ?></span>
								<?php if ( is_rtl() ) { ?>
									<span class="ld-icon ld-icon-arrow-left"></span>
								<?php } else { ?>
									<span class="ld-icon ld-icon-arrow-right"></span>
								<?php } ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php elseif ( $parent_id && 'focus' === $context ) : ?>
				<div class="ld-content-action
				<?php
				if ( ( ! $can_complete ) && ( ! $learndash_next_step_id ) ) :
					?>
					ld-empty<?php endif; ?>">
					<?php
					if ( ( true === $can_complete ) && ( true !== $current_complete ) && ( ! empty( $complete_button ) ) ) :
						echo learndash_mark_complete( $course_step_post );
					elseif ( ( true === $can_complete ) && ( true === $current_complete ) && ( ! empty( $incomplete_button ) ) ) :
						echo $incomplete_button;
					elseif ( $learndash_next_step_id ) :
						?>
						<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_attr( learndash_get_step_permalink( $learndash_next_step_id, $course_id ) ); ?>">
							<span class="ld-text"><?php echo learndash_get_label_course_step_next( get_post_type( $learndash_next_step_id ) ); ?></span>
							<?php if ( is_rtl() ) { ?>
								<span class="ld-icon ld-icon-arrow-left"></span>
							<?php } else { ?>
								<span class="ld-icon ld-icon-arrow-right"></span>
							<?php } ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php
			/**
			 * Fires after the course steps (all locations).
			 *
			 * @since 3.0.0
			 *
			 * @param string|false $post_type Post type slug.
			 * @param int          $course_id Course ID.
			 * @param int          $user_id   User ID.
			 */
			do_action( 'learndash-all-course-steps-after', get_post_type(), $course_id, $user_id );

			/**
			 * Fires after the course steps for any context.
			 *
			 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
			 * such as `course`, `lesson`, `topic`, `quiz`, etc.
			 *
			 * @since 3.0.0
			 *
			 * @param string|false $post_type Post type slug.
			 * @param int          $course_id Course ID.
			 * @param int          $user_id   User ID.
			 */
			do_action( 'learndash-' . $context . '-course-steps-after', get_post_type(), $course_id, $user_id );
			?>

		</div> <!--/.ld-topic-actions-->

	</div> <!--/.ld-content-actions-->

</nav> <!--/.bb-rl-ld-module-footer-->

<?php
// endif;
?>
