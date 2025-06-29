<?php
/**
 * Template for quiz row for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/quizzes/section_quiz_row.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\quizzes\models as models;

?>

<div id="mpcs-lesson-<?php echo esc_attr( $lesson->ID ); ?>" class="mpcs-lesson 
								<?php
								if ( ! $lesson_available ) {
									echo 'locked ';
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
				<?php elseif ( $lesson_available ) : ?>
					<span class="mpcs-lesson-not-complete"><i class="mpcs-circle-regular"></i></span>
				<?php else : ?>
					<span class="mpcs-lesson-locked"><i class="mpcs-circle-regular"></i></span>
				<?php endif; ?>
			</div>
			<div class="mpcs-lesson-link">
				<i class="<?php echo esc_attr( $lesson->post_type ); ?>-icon"></i>
				<?php echo esc_html( $lesson->post_title ); ?>
				<?php do_action( 'mpcs_section_lesson_title_suffix', $lesson, $has_completed_lesson ); ?>
				<?php if ( $has_completed_lesson && $attempt instanceof models\Attempt && $attempt->is_complete() ) : ?>
					<span class="mpcs-lesson-list-quiz-score">(<?php echo esc_html( $attempt->get_score_percent() ); ?>)</span>
				<?php endif; ?>
			</div>
			<div class="mpcs-lesson-button">
				<span class="mpcs-button" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
					<?php if ( $has_completed_lesson ) : ?>
						<span class="mpcs-button is-outline"
							href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
							<?php esc_html_e( 'View', 'buddyboss-pro' ); ?>
						</span>
					<?php elseif ( $lesson_available ) : ?>
						<span class="mpcs-button is-purple"
							href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
							<?php if ( $attempt instanceof models\Attempt && $attempt->is_draft() ) : ?>
								<?php esc_html_e( 'Continue', 'buddyboss-pro' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Start', 'buddyboss-pro' ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</span>
			</div>
			<?php if ( $lesson_available ) : ?>
	</a>
<?php else : ?>
	</span>
	<span class="mpcs-lesson-locked-tooltip"><?php esc_html_e( 'Lesson unavailable. You must complete all previous lessons and quizzes before you start this lesson.', 'buddyboss-pro' ); ?></span>
<?php endif; ?>
</div>
