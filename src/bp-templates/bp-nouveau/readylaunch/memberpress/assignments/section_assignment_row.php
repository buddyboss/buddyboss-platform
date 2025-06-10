<?php
/**
 * Template for assignment row for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/assignments/section_assignment_row.php.
 *
 * @since 2.6.30
 * 
 * @package BuddyBoss\MemberpressLMS
 */

?>

<div id="mpcs-lesson-<?php echo esc_attr( $lesson->ID ); ?>" class="mpcs-lesson 
								<?php
								if ( $has_completed_lesson ) {
									echo 'completed ';
								} elseif ( ! $lesson_available ) {
									echo 'locked ';
								}
								if ( $lesson_available && $is_sidebar && get_the_ID() === $lesson->ID ) {
									echo 'current ';
								}
								if ( $show_bookmark && isset( $next_lesson->ID ) && $next_lesson->ID === $lesson->ID ) {
									echo 'current ';
								}
								?>
">

	<?php if ( $lesson_available ) : ?>
	<a href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>" class="mpcs-lesson-row-link">
		<?php else : ?>
		<span class="mpcs-lesson-row-link">
	<?php endif; ?>
			<div class="mpcs-lesson-progress">
				<?php if ( $has_completed_lesson ) : ?>
					<span class="mpcs-lesson-complete"><i class="mpcs-ok-circled"></i></span>
				<?php elseif ( $lesson_available && ( $is_sidebar && get_the_ID() === $lesson->ID ) || ( $show_bookmark && $next_lesson->ID === $lesson->ID ) ) : ?>
					<span class="mpcs-lesson-current"><i class="mpcs-adjust-solid"></i></span>
				<?php else : ?>
					<span class="mpcs-lesson-not-complete"><i class="mpcs-circle-regular"></i></span>
				<?php endif; ?>
			</div>
			<div class="mpcs-lesson-link">
				<i class="<?php echo esc_attr( $lesson->post_type ); ?>-icon"></i>
				<?php echo esc_html( $lesson->post_title ); ?>
				<?php

				if ( ! $is_sidebar && $has_submission ) {
					if ( $has_completed_lesson ) {
						printf( '<span class="mpcs-lesson-list-quiz-score">(%s)</span>', esc_html( $submission->get_score_percent() ) );
					} else {
						printf( '<span class="mpcs-lesson-list-quiz-score">(%s)</span>', esc_html__( 'Grade Pending', 'buddyboss-pro' ) );
					}
				}
				?>
				<?php do_action( 'mpcs_section_lesson_title_suffix', $lesson, $has_completed_lesson, $is_sidebar ); ?>
			</div>
			<div class="mpcs-lesson-button">

				<?php if ( is_user_logged_in() ) : ?>
					<span class="mpcs-button">
						<?php if ( $has_completed_lesson ) : ?>
							<span class="btn is-outline" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
								<?php esc_html_e( 'View', 'buddyboss-pro' ); ?>
							</span>

						<?php elseif ( $lesson_available ) : ?>
							<span class="btn btn-green is-purple"
								href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
								<?php esc_html_e( 'Start', 'buddyboss-pro' ); ?>
							</span>
						<?php endif; ?>
					</span>
				<?php endif; ?>

			</div>
			<?php if ( $lesson_available ) : ?>
	</a>
<?php else : ?>
	</span>
	<span class="mpcs-lesson-locked-tooltip"><?php esc_html_e( 'Lesson unavailable. You must complete all previous lessons and quizzes before you start this lesson.', 'buddyboss-pro' ); ?></span>
<?php endif; ?>
</div>
